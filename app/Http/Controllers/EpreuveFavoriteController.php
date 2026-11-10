<?php

namespace App\Http\Controllers;

use App\Models\Epreuve;
use App\Models\EpreuveFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EpreuveFavoriteController extends Controller
{
    public function store(Request $request, Epreuve $epreuve): RedirectResponse
    {
        abort_unless($epreuve->status === 'published', 404);

        EpreuveFavorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'epreuve_id' => $epreuve->id,
        ]);

        return back();
    }

    public function destroy(Request $request, Epreuve $epreuve): RedirectResponse
    {
        abort_unless($epreuve->status === 'published', 404);

        EpreuveFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('epreuve_id', $epreuve->id)
            ->delete();

        return back();
    }
}
