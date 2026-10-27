<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFiliereRequest;
use App\Http\Requests\UpdateFiliereRequest;
use App\Models\Filiere;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FiliereController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
        ]);
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? 'all';

        $filieres = Filiere::query()
            ->withCount('associations')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status !== 'all', fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Filiere $filiere): array => [
                'id' => $filiere->id,
                'code' => $filiere->code,
                'name' => $filiere->name,
                'is_active' => $filiere->is_active,
                'associations_count' => $filiere->associations_count,
            ]);

        return Inertia::render('Referentiels/ReferentielsIndex', [
            'title' => 'Filières',
            'resource' => 'filieres',
            'items' => $filieres,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Referentiels/ReferentielForm', [
            'title' => 'Créer une filière',
            'resource' => 'filieres',
            'item' => null,
        ]);
    }

    public function store(StoreFiliereRequest $request): RedirectResponse
    {
        Filiere::create($request->validated());

        return to_route('admin.filieres.index')->with('status', 'Filière créée.');
    }

    public function edit(Filiere $filiere): Response
    {
        return Inertia::render('Referentiels/ReferentielForm', [
            'title' => 'Modifier une filière',
            'resource' => 'filieres',
            'item' => [
                'id' => $filiere->id,
                'code' => $filiere->code,
                'name' => $filiere->name,
                'code_locked' => $filiere->isUsed(),
            ],
        ]);
    }

    public function update(UpdateFiliereRequest $request, Filiere $filiere): RedirectResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $filiere): RedirectResponse {
            $lockedFiliere = Filiere::query()
                ->whereKey($filiere->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedFiliere->isUsed() && $data['code'] !== $lockedFiliere->code) {
                throw ValidationException::withMessages([
                    'code' => 'Le code ne peut plus être modifié après utilisation.',
                ]);
            }

            $lockedFiliere->update($data);

            return to_route('admin.filieres.index')->with('status', 'Filière modifiée.');
        });
    }

    public function destroy(Filiere $filiere): RedirectResponse
    {
        $wasUsed = DB::transaction(function () use ($filiere): bool {
            $lockedFiliere = Filiere::query()
                ->whereKey($filiere->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedFiliere->isUsed()) {
                $lockedFiliere->update(['is_active' => false]);

                return true;
            }

            $lockedFiliere->delete();

            return false;
        });

        return back()->with(
            'status',
            $wasUsed ? 'Cette filière est utilisée; elle a été désactivée.' : 'Filière supprimée.',
        );
    }

    public function deactivate(Filiere $filiere): RedirectResponse
    {
        $filiere->update(['is_active' => false]);

        return back()->with('status', 'Filière désactivée.');
    }

    public function reactivate(Filiere $filiere): RedirectResponse
    {
        $filiere->update(['is_active' => true]);

        return back()->with('status', 'Filière réactivée.');
    }
}
