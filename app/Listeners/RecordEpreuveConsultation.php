<?php

namespace App\Listeners;

use App\Events\EpreuveConsultee;
use App\Models\EpreuveActivity;

class RecordEpreuveConsultation
{
    public function handle(EpreuveConsultee $event): void
    {
        EpreuveActivity::query()->create([
            'user_id' => $event->userId,
            'epreuve_id' => $event->epreuveId,
            'action' => 'consultation',
            'occurred_at' => now(),
        ]);
    }
}
