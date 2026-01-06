<?php

namespace App\Console\Commands;

use App\Models\Metric;
use Illuminate\Console\Command;

class PruneMetrics extends Command
{
    protected $signature = 'ned:prune-metrics {--days=365 : Number of days to retain}';

    protected $description = 'Prune old metrics (default: 1 year retention)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = Metric::where('recorded_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('No old metrics to prune.');

            return Command::SUCCESS;
        }

        $this->info("Pruning {$count} metrics older than {$days} days...");

        // Delete in chunks to avoid memory issues with large datasets
        Metric::where('recorded_at', '<', $cutoff)->delete();

        $this->info('Done.');

        return Command::SUCCESS;
    }
}
