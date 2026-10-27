<?php

namespace App\Http\Controllers\Admin;

use App\AssignableUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Models\UserAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'include_deleted' => ['sometimes', 'boolean'],
        ]);

        $search = trim($filters['search'] ?? '');
        $users = User::query()
            ->with('roles:id,name')
            ->when(
                $request->boolean('include_deleted'),
                static fn ($query) => $query->withTrashed(),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'is_active' => $user->is_active,
                'is_deleted' => $user->trashed(),
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
            ]);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', AssignableUserRole::values())
            ->orderBy('id')
            ->get(['name'])
            ->map(static fn (Role $role): array => [
                'value' => $role->name,
                'label' => AssignableUserRole::from($role->name)->label(),
            ]);

        $audits = UserAudit::query()
            ->with([
                'actor' => static fn ($query) => $query->withTrashed()->select(['id', 'name']),
                'user' => static fn ($query) => $query->withTrashed()->select(['id', 'name']),
            ])
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(static fn (UserAudit $audit): array => [
                'action' => $audit->action,
                'actor' => $audit->actor?->name ?? 'Système',
                'user' => $audit->user?->name ?? 'Compte supprimé',
                'details' => $audit->details ?? [],
                'created_at' => $audit->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Users/UsersManagementPage', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'include_deleted' => $request->boolean('include_deleted'),
            ],
            'audits' => $audits,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        if (
            in_array(config('mail.default'), ['array', 'log'], true)
            && ! app()->runningUnitTests()
        ) {
            throw ValidationException::withMessages([
                'email' => 'Le service de courriel n’envoie pas de messages. Aucun compte n’a été créé.',
            ]);
        }

        $attributes = $request->validated();

        DB::transaction(function () use ($attributes, $request): void {
            $role = Role::query()
                ->where('name', $attributes['role'])
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => str()->random(64),
            ]);
            $user->syncRoles($role);

            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status !== Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    'email' => 'Le lien de définition du mot de passe n’a pas pu être envoyé. Aucun compte n’a été créé.',
                ]);
            }

            $this->recordAudit($user, 'created', [
                'role' => $role->name,
                'fields' => ['name', 'email', 'role'],
            ], $request);
        });

        return to_route('admin.users.index')
            ->with('status', 'Le compte a été créé et le lien de définition du mot de passe envoyé.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $attributes = $request->validated();

        DB::transaction(function () use ($attributes, $request, $user): void {
            $this->lockAdministratorRole();
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $previousRoles = $user->getRoleNames()->all();
            $wasAdministrator = in_array(
                AssignableUserRole::Administrator->value,
                $previousRoles,
                true,
            );
            $willRemainAdministrator = $attributes['role'] === AssignableUserRole::Administrator->value;

            if (
                $user->is_active
                && $wasAdministrator
                && ! $willRemainAdministrator
            ) {
                $this->ensureAnotherActiveAdministratorExists();
            }

            $role = Role::query()
                ->where('name', $attributes['role'])
                ->where('guard_name', 'web')
                ->firstOrFail();

            $changedFields = [];

            if ($user->name !== $attributes['name']) {
                $changedFields[] = 'name';
            }

            if ($user->email !== $attributes['email']) {
                $changedFields[] = 'email';
            }

            if ($previousRoles !== [$role->name]) {
                $changedFields[] = 'role';
            }

            $user->update([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
            ]);
            $user->syncRoles($role);

            if ($changedFields !== []) {
                $this->recordAudit($user, 'updated', [
                    'fields' => $changedFields,
                    'previous_roles' => $previousRoles,
                    'role' => $role->name,
                ], $request);
            }
        });

        return to_route('admin.users.index')->with('status', 'Le compte a été mis à jour.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        $isActive = (bool) $request->validated('is_active');

        DB::transaction(function () use ($isActive, $request, $user): void {
            $this->lockAdministratorRole();
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($user->is_active === $isActive) {
                return;
            }

            if (! $isActive && $user->is($request->user())) {
                throw ValidationException::withMessages([
                    'is_active' => 'Tu ne peux pas désactiver ton propre compte.',
                ]);
            }

            if (! $isActive && $user->hasRole(AssignableUserRole::Administrator->value)) {
                $this->ensureAnotherActiveAdministratorExists();
            }

            $previousStatus = $user->is_active;
            $user->update(['is_active' => $isActive]);

            $this->recordAudit($user, $isActive ? 'activated' : 'deactivated', [
                'previous_is_active' => $previousStatus,
                'is_active' => $isActive,
            ], $request);
        });

        return to_route('admin.users.index')->with('status', 'Le statut du compte a été mis à jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $this->lockAdministratorRole();
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($user->is($request->user())) {
                throw ValidationException::withMessages([
                    'user' => 'Tu ne peux pas supprimer ton propre compte.',
                ]);
            }

            if ($user->is_active && $user->hasRole(AssignableUserRole::Administrator->value)) {
                $this->ensureAnotherActiveAdministratorExists();
            }

            $user->delete();

            $this->recordAudit($user, 'deleted', [
                'soft_delete' => true,
            ], $request);
        });

        return to_route('admin.users.index')
            ->with('status', 'Le compte a été supprimé logiquement.');
    }

    public function restore(Request $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $this->lockAdministratorRole();
            $user = User::withTrashed()->lockForUpdate()->findOrFail($user->getKey());

            if (! $user->trashed()) {
                return;
            }

            $user->restore();

            $this->recordAudit($user, 'restored', [
                'is_active' => $user->is_active,
            ], $request);
        });

        return to_route('admin.users.index')
            ->with('status', 'Le compte a été restauré.');
    }

    private function lockAdministratorRole(): void
    {
        Role::query()
            ->where('name', AssignableUserRole::Administrator->value)
            ->where('guard_name', 'web')
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureAnotherActiveAdministratorExists(): void
    {
        $activeAdministrators = User::query()
            ->where('is_active', true)
            ->whereHas('roles', static fn ($query) => $query
                ->where('name', AssignableUserRole::Administrator->value)
                ->where('guard_name', 'web'))
            ->count();

        if ($activeAdministrators <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Le dernier administrateur actif ne peut pas être rétrogradé, désactivé ou supprimé.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function recordAudit(User $user, string $action, array $details, Request $request): void
    {
        UserAudit::create([
            'actor_id' => $request->user()?->getKey(),
            'user_id' => $user->getKey(),
            'action' => $action,
            'details' => $details,
            'created_at' => now(),
        ]);
    }
}
