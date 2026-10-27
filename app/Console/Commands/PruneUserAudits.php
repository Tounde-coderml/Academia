<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('user-audits:prune')]
#[Description('Delete user audit entries older than the approved retention period')]
class PruneUserAudits extends Command
{
    public function handle(): int
    {
        $deleted = DB::table('user_audits')
            ->where('created_at', '<', now()->subMonths(12))
            ->delete();

        $this->info("{$deleted} audit(s) de compte supprimé(s) selon la règle de rétention de 12 mois.");

        return self::SUCCESS;
    }
}
