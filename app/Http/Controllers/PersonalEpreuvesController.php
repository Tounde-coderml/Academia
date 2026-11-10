<?php

namespace App\Http\Controllers;

use App\Models\Epreuve;
use App\Models\EpreuveActivity;
use App\Models\EpreuveFavorite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonalEpreuvesController extends Controller
{
    public function favorites(Request $request): Response
    {
        $epreuves = EpreuveFavorite::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('epreuve', fn (Builder $query): Builder => $query->where('status', 'published'))
            ->with(['epreuve.filiere:id,name', 'epreuve.niveau:id,name', 'epreuve.matiere:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (EpreuveFavorite $favorite): array => $this->epreuveData($favorite->epreuve));

        return Inertia::render('Personal/PersonalEpreuves', [
            'mode' => 'favorites',
            'epreuves' => $epreuves,
        ]);
    }

    public function history(Request $request): Response
    {
        $epreuves = EpreuveActivity::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('action', ['consultation', 'telechargement'])
            ->whereHas('epreuve', fn (Builder $query): Builder => $query->where('status', 'published'))
            ->with(['epreuve.filiere:id,name', 'epreuve.niveau:id,name', 'epreuve.matiere:id,name'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->through(fn (EpreuveActivity $activity): array => [
                ...$this->epreuveData($activity->epreuve),
                'action' => $activity->action,
                'occurred_at' => $activity->occurred_at->toDateTimeString(),
            ]);

        return Inertia::render('Personal/PersonalEpreuves', [
            'mode' => 'history',
            'epreuves' => $epreuves,
        ]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function epreuveData(Epreuve $epreuve): array
    {
        return [
            'id' => $epreuve->id,
            'title' => $epreuve->title,
            'filiere' => $epreuve->filiere->name,
            'niveau' => $epreuve->niveau->name,
            'matiere' => $epreuve->matiere->name,
            'academic_year' => $epreuve->academic_year,
            'semester' => 'S'.$epreuve->semester,
            'type' => $epreuve->type,
        ];
    }
}
