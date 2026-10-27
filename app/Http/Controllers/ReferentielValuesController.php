<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ReferentielValuesController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'filieres' => Filiere::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'code', 'name']),
            'niveaux' => Niveau::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'code', 'name', 'sort_order']),
            'matieres' => Matiere::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'code', 'name']),
            'associations' => FiliereNiveauMatiere::query()
                ->whereHas('filiere', fn (Builder $query) => $query->where('is_active', true))
                ->whereHas('niveau', fn (Builder $query) => $query->where('is_active', true))
                ->whereHas('matiere', fn (Builder $query) => $query->where('is_active', true))
                ->orderBy('filiere_id')
                ->orderBy('niveau_id')
                ->orderBy('matiere_id')
                ->get(['filiere_id', 'niveau_id', 'matiere_id']),
        ]);
    }
}
