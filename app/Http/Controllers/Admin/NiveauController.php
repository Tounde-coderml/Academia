<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNiveauRequest;
use App\Http\Requests\UpdateNiveauRequest;
use App\Models\Niveau;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NiveauController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
        ]);
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? 'all';

        $niveaux = Niveau::query()
            ->withCount('associations')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Niveau $niveau): array => [
                'id' => $niveau->id,
                'code' => $niveau->code,
                'name' => $niveau->name,
                'sort_order' => $niveau->sort_order,
                'is_active' => $niveau->is_active,
                'associations_count' => $niveau->associations_count,
            ]);

        return Inertia::render('Referentiels/ReferentielsIndex', [
            'title' => 'Niveaux',
            'resource' => 'niveaux',
            'items' => $niveaux,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Referentiels/ReferentielForm', [
            'title' => 'Créer un niveau',
            'resource' => 'niveaux',
            'item' => null,
        ]);
    }

    public function store(StoreNiveauRequest $request): RedirectResponse
    {
        Niveau::create($request->validated());

        return to_route('admin.niveaux.index')->with('status', 'Niveau créé.');
    }

    public function edit(Niveau $niveau): Response
    {
        return Inertia::render('Referentiels/ReferentielForm', [
            'title' => 'Modifier un niveau',
            'resource' => 'niveaux',
            'item' => [
                'id' => $niveau->id,
                'code' => $niveau->code,
                'name' => $niveau->name,
                'sort_order' => $niveau->sort_order,
                'code_locked' => $niveau->isUsed(),
            ],
        ]);
    }

    public function update(UpdateNiveauRequest $request, Niveau $niveau): RedirectResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $niveau): RedirectResponse {
            $lockedNiveau = Niveau::query()
                ->whereKey($niveau->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedNiveau->isUsed() && $data['code'] !== $lockedNiveau->code) {
                throw ValidationException::withMessages([
                    'code' => 'Le code ne peut plus être modifié après utilisation.',
                ]);
            }

            $lockedNiveau->update($data);

            return to_route('admin.niveaux.index')->with('status', 'Niveau modifié.');
        });
    }

    public function destroy(Niveau $niveau): RedirectResponse
    {
        $wasUsed = DB::transaction(function () use ($niveau): bool {
            $lockedNiveau = Niveau::query()
                ->whereKey($niveau->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedNiveau->isUsed()) {
                $lockedNiveau->update(['is_active' => false]);

                return true;
            }

            $lockedNiveau->delete();

            return false;
        });

        return back()->with(
            'status',
            $wasUsed ? 'Ce niveau est utilisé; il a été désactivé.' : 'Niveau supprimé.',
        );
    }

    public function deactivate(Niveau $niveau): RedirectResponse
    {
        $niveau->update(['is_active' => false]);

        return back()->with('status', 'Niveau désactivé.');
    }

    public function reactivate(Niveau $niveau): RedirectResponse
    {
        $niveau->update(['is_active' => true]);

        return back()->with('status', 'Niveau réactivé.');
    }
}
