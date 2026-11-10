<?php

namespace App\Listeners;

use App\Events\EpreuveTelechargee;
use App\Models\EpreuveActivity;

class RecordEpreuveDownload
{
    public function handle(EpreuveTelechargee $event): void
    {
        EpreuveActivity::query()->create([
            'user_id' => $event->userId,
            'epreuve_id' => $event->epreuveId,
            'action' => 'telechargement',
            'occurred_at' => now(),
        ]);
    }
}
