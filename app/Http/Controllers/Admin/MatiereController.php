<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFiliereNiveauMatiereRequest;
use App\Http\Requests\StoreMatiereRequest;
use App\Http\Requests\UpdateMatiereRequest;
use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MatiereController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'active', 'inactive'])],
        ]);
        $search = $filters['search'] ?? '';
        $status = $filters['status'] ?? 'all';

        $matieres = Matiere::query()
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
            ->through(fn (Matiere $matiere): array => [
                'id' => $matiere->id,
                'code' => $matiere->code,
                'name' => $matiere->name,
                'is_active' => $matiere->is_active,
                'associations_count' => $matiere->associations_count,
            ]);

        return Inertia::render('Referentiels/ReferentielsIndex', [
            'title' => 'Matières',
            'resource' => 'matieres',
            'items' => $matieres,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Referentiels/ReferentielForm', [
            'title' => 'Créer une matière',
            'resource' => 'matieres',
            'item' => null,
        ]);
    }

    public function store(StoreMatiereRequest $request): RedirectResponse
    {
        Matiere::create($request->validated());

        return to_route('admin.matieres.index')->with('status', 'Matière créée.');
    }

    public function edit(Matiere $matiere): Response
    {
        return Inertia::render('Referentiels/ReferentielForm', [
            'title' => 'Modifier une matière',
            'resource' => 'matieres',
            'item' => [
                'id' => $matiere->id,
                'code' => $matiere->code,
                'name' => $matiere->name,
                'code_locked' => $matiere->isUsed(),
            ],
        ]);
    }

    public function update(UpdateMatiereRequest $request, Matiere $matiere): RedirectResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $matiere): RedirectResponse {
            $lockedMatiere = Matiere::query()
                ->whereKey($matiere->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMatiere->isUsed() && $data['code'] !== $lockedMatiere->code) {
                throw ValidationException::withMessages([
                    'code' => 'Le code ne peut plus être modifié après utilisation.',
                ]);
            }

            $lockedMatiere->update($data);

            return to_route('admin.matieres.index')->with('status', 'Matière modifiée.');
        });
    }

    public function destroy(Matiere $matiere): RedirectResponse
    {
        $wasUsed = DB::transaction(function () use ($matiere): bool {
            $lockedMatiere = Matiere::query()
                ->whereKey($matiere->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMatiere->isUsed()) {
                $lockedMatiere->update(['is_active' => false]);

                return true;
            }

            $lockedMatiere->delete();

            return false;
        });

        return back()->with(
            'status',
            $wasUsed ? 'Cette matière est utilisée; elle a été désactivée.' : 'Matière supprimée.',
        );
    }

    public function deactivate(Matiere $matiere): RedirectResponse
    {
        $matiere->update(['is_active' => false]);

        return back()->with('status', 'Matière désactivée.');
    }

    public function reactivate(Matiere $matiere): RedirectResponse
    {
        $matiere->update(['is_active' => true]);

        return back()->with('status', 'Matière réactivée.');
    }

    public function associations(Matiere $matiere): Response
    {
        $associations = $matiere->associations()
            ->with([
                'filiere:id,code,name,is_active',
                'niveau:id,code,name,is_active',
            ])
            ->orderBy('filiere_id')
            ->orderBy('niveau_id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (FiliereNiveauMatiere $association): array => [
                'id' => $association->id,
                'filiere' => $association->filiere,
                'niveau' => $association->niveau,
            ]);

        return Inertia::render('Referentiels/MatiereAssociations', [
            'matiere' => [
                'id' => $matiere->id,
                'code' => $matiere->code,
                'name' => $matiere->name,
                'is_active' => $matiere->is_active,
            ],
            'associations' => $associations,
            'filieres' => Filiere::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'niveaux' => Niveau::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'sort_order']),
        ]);
    }

    public function storeAssociation(
        StoreFiliereNiveauMatiereRequest $request,
        Matiere $matiere,
    ): RedirectResponse {
        if (! $matiere->is_active) {
            return back()->withErrors([
                'matiere' => 'Une matière inactive ne peut pas recevoir de nouvelle association.',
            ]);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $matiere): void {
            $filiere = Filiere::query()
                ->whereKey($data['filiere_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $niveau = Niveau::query()
                ->whereKey($data['niveau_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $lockedMatiere = Matiere::query()
                ->whereKey($matiere->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $filiere->is_active || ! $niveau->is_active || ! $lockedMatiere->is_active) {
                throw ValidationException::withMessages([
                    'filiere_id' => 'Les nouvelles associations exigent une filière, un niveau et une matière actifs.',
                ]);
            }

            if (
                FiliereNiveauMatiere::query()
                    ->where('filiere_id', $filiere->id)
                    ->where('niveau_id', $niveau->id)
                    ->where('matiere_id', $lockedMatiere->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'filiere_id' => 'Cette association existe déjà.',
                ]);
            }

            FiliereNiveauMatiere::create([
                'filiere_id' => $filiere->id,
                'niveau_id' => $niveau->id,
                'matiere_id' => $lockedMatiere->id,
            ]);
        });

        return back()->with('status', 'Association ajoutée.');
    }

    public function destroyAssociation(
        Matiere $matiere,
        FiliereNiveauMatiere $association,
    ): RedirectResponse {
        DB::transaction(function () use ($matiere, $association): void {
            $lockedMatiere = Matiere::query()
                ->whereKey($matiere->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedMatiere->associations()
                ->whereKey($association->getKey())
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();
        });

        return back()->with('status', 'Association supprimée.');
    }
}
