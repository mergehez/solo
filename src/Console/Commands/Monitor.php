<?php

namespace AaronFrancis\Solo\Console\Commands;

use AaronFrancis\Solo\Support\ProcessTracker;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class Monitor extends Command
{
    protected $signature = 'solo:monitor {pid}';

    protected $description = 'Watch for the stray processes and clean them up.';

    public function handle()
    {
        $parent = $this->argument('pid');
        $children = [];

        $this->info("Monitoring parent process PID: {$parent}");

        while (true) {
            $children = array_unique([
                ...$children,
                ...ProcessTracker::children($parent)
            ]);

            // Every 10 seconds cull the children that are no longer running.
            if (Carbon::now()->second % 10 === 0) {
                $children = ProcessTracker::running($children);
            }

            sleep(1);

            if (ProcessTracker::isRunning($parent)) {
                continue;
            }

            $this->warn("Parent process {$parent} has died.");

            // Give them a chance to die on their own.
            sleep(2);

            // Don't kill ourselves.
            $children = array_diff($children, [getmypid()]);

            ProcessTracker::kill($children);

            $this->warn('Killed processes: ' . implode(', ', $children));

            $this->info('All tracked child processes cleaned up. Exiting.');

            break;
        }
    }
}
