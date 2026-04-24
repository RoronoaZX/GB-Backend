<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArchiveOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:prune-recipe-costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune recipe cost change logs older than 1 year to optimize database size.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pruning of recipe cost change logs...');

        $count = \App\Models\RecipeCostChangeLog::where('created_at', '<', now()->subYear())
            ->delete();

        $this->info("Successfully pruned {$count} logs older than 1 year.");
    }
}
