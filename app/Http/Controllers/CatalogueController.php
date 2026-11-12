<?php

namespace App\Http\Controllers;

use App\Events\EpreuveConsultee;
use App\Http\Requests\CatalogueSearchRequest;
use App\Models\Epreuve;
use App\Models\Filiere;
use App\Models\FiliereNiveauMatiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CatalogueController extends Controller
{
    private const SEARCH_ACCENT_REPLACEMENTS = [
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'ã' => 'a',
        'ä' => 'a',
        'å' => 'a',
        'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e',
        'é' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'ì' => 'i',
        'í' => 'i',
        'î' => 'i',
        'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ö' => 'o',
        'œ' => 'oe',
        'ù' => 'u',
        'ú' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'ý' => 'y',
        'ÿ' => 'y',
    ];

    public function index(CatalogueSearchRequest $request): Response
    {
        return $this->catalogue($request);
    }

    public function byFiliere(CatalogueSearchRequest $request, Filiere $filiere): Response
    {
        abort_unless($filiere->is_active, 404);

        $matieres = FiliereNiveauMatiere::query()
            ->where('filiere_id', $filiere->id)
            ->whereHas('niveau', fn (Builder $query): Builder => $query->where('is_active', true))
            ->whereHas('matiere', fn (Builder $query): Builder => $query->where('is_active', true))
            ->with('matiere:id,name')
            ->get()
            ->pluck('matiere')
            ->unique('id')
            ->values()
            ->map(fn (Matiere $matiere): array => ['id' => $matiere->id, 'name' => $matiere->name]);

        return Inertia::render('Catalogue/FiliereShow', [
            'filiere' => ['id' => $filiere->id, 'code' => $filiere->code, 'name' => $filiere->name],
            'matieres' => $matieres,
        ]);
    }

    public function byMatiere(CatalogueSearchRequest $request, Matiere $matiere): Response
    {
        abort_unless($matiere->is_active, 404);

        return $this->catalogue($request, ['matiere_id' => $matiere->id], 'Matière '.$matiere->name);
    }

    public function show(Epreuve $epreuve): Response
    {
        abort_unless($epreuve->status === 'published', 404);
        $epreuve->load(['filiere:id,code,name', 'niveau:id,code,name', 'matiere:id,code,name']);
        EpreuveConsultee::dispatch((int) request()->user()->id, $epreuve->id);

        return Inertia::render('Catalogue/EpreuveShow', [
            'epreuve' => [
                'id' => $epreuve->id,
                'title' => $epreuve->title,
                'filiere' => $epreuve->filiere->name,
                'filiere_id' => $epreuve->filiere_id,
                'niveau' => $epreuve->niveau->name,
                'matiere' => $epreuve->matiere->name,
                'matiere_id' => $epreuve->matiere_id,
                'academic_year' => $epreuve->academic_year,
                'semester' => 'S'.$epreuve->semester,
                'type' => $epreuve->type,
                'published_at' => $epreuve->published_at?->toDateTimeString(),
                'file_size' => $epreuve->file_size,
                'can_preview' => in_array($epreuve->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true),
                'is_favorite' => $epreuve->favorites()->where('user_id', request()->user()->id)->exists(),
            ],
        ]);
    }

    /**
     * @param  array<string, int>  $fixedFilters
     */
    private function catalogue(
        CatalogueSearchRequest $request,
        array $fixedFilters = [],
        string $title = 'Catalogue des épreuves',
    ): Response {
        $filters = [...$request->validated(), ...$fixedFilters];
        $query = Epreuve::query()
            ->where('status', 'published')
            ->withExists([
                'favorites as is_favorite' => fn (Builder $query): Builder => $query->where('user_id', $request->user()->id),
            ])
            ->with(['filiere:id,code,name', 'niveau:id,code,name', 'matiere:id,code,name']);

        if (($keyword = $filters['q'] ?? null) !== null && $keyword !== '') {
            $searchTerm = addcslashes(Str::ascii(Str::lower($keyword)), '\\%_');

            $query->where(function (Builder $query) use ($searchTerm): void {
                $this->whereSearchMatches($query, 'title', $searchTerm)
                    ->orWhereHas('matiere', fn (Builder $related): Builder => $this->whereSearchMatches($related, 'name', $searchTerm))
                    ->orWhereHas('filiere', fn (Builder $related): Builder => $this->whereSearchMatches($related, 'name', $searchTerm));
            });
        }

        foreach (['filiere_id', 'niveau_id', 'matiere_id'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (isset($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        if (isset($filters['academic_year'])) {
            $query->where('academic_year', $filters['academic_year']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        match ($filters['sort'] ?? 'recent') {
            'year' => $query->orderByDesc('academic_year')->orderByDesc('published_at')->orderByDesc('id'),
            'title' => $query->orderBy('title')->orderBy('id'),
            'subject' => $query->orderBy(
                Matiere::query()->select('name')->whereColumn('matieres.id', 'epreuves.matiere_id'),
            )->orderBy('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $epreuves = $query->paginate(20)->withQueryString();

        return Inertia::render('Catalogue/CatalogueIndex', [
            'title' => $title,
            'action' => $request->url(),
            'epreuves' => $epreuves->through(fn (Epreuve $epreuve): array => [
                'id' => $epreuve->id,
                'title' => $epreuve->title,
                'filiere' => $epreuve->filiere->name,
                'filiere_id' => $epreuve->filiere_id,
                'niveau' => $epreuve->niveau->name,
                'matiere' => $epreuve->matiere->name,
                'matiere_id' => $epreuve->matiere_id,
                'academic_year' => $epreuve->academic_year,
                'semester' => 'S'.$epreuve->semester,
                'type' => $epreuve->type,
                'published_at' => $epreuve->published_at?->toDateTimeString(),
                'is_favorite' => (bool) $epreuve->is_favorite,
            ]),
            'filters' => [
                'q' => $filters['q'] ?? '',
                'filiere_id' => $filters['filiere_id'] ?? '',
                'niveau_id' => $filters['niveau_id'] ?? '',
                'matiere_id' => $filters['matiere_id'] ?? '',
                'semester' => $filters['semester'] ?? '',
                'academic_year' => $filters['academic_year'] ?? '',
                'type' => $filters['type'] ?? '',
                'sort' => $filters['sort'] ?? 'recent',
            ],
            'options' => [
                'filieres' => Filiere::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'niveaux' => Niveau::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
                'matieres' => Matiere::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'years' => Epreuve::query()->where('status', 'published')->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
                'semesters' => array_map(
                    static fn (array $semester): array => ['id' => $semester['id'], 'name' => $semester['code']],
                    Semestre::options(),
                ),
                'types' => [
                    ['id' => 'examen', 'name' => 'Examen'],
                    ['id' => 'rattrapage', 'name' => 'Rattrapage'],
                    ['id' => 'devoir', 'name' => 'Devoir'],
                    ['id' => 'controle_continu', 'name' => 'Contrôle continu'],
                ],
            ],
            'resultCount' => $epreuves->total(),
        ]);
    }

    private function whereSearchMatches(Builder $query, string $column, string $searchTerm): Builder
    {
        $expression = 'LOWER('.$query->getQuery()->getGrammar()->wrap($column).')';

        foreach (self::SEARCH_ACCENT_REPLACEMENTS as $accent => $replacement) {
            $expression = "REPLACE({$expression}, '{$accent}', '{$replacement}')";
        }

        return $query->whereRaw($expression.' LIKE ?', ['%'.$searchTerm.'%']);
    }
}
