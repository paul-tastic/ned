<?php

namespace App\Console\Commands;

use App\Models\BannedIpEvent;
use Illuminate\Console\Command;

class PruneBannedIpEvents extends Command
{
    protected $signature = 'ned:prune-banned-ips {--days=365 : Number of days to retain}';

    protected $description = 'Prune old banned IP events (default: 1 year retention)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = BannedIpEvent::where('event_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('No old banned IP events to prune.');

            return Command::SUCCESS;
        }

        $this->info("Pruning {$count} banned IP events older than {$days} days...");

        BannedIpEvent::where('event_at', '<', $cutoff)->delete();

        $this->info('Done.');

        return Command::SUCCESS;
    }
}
