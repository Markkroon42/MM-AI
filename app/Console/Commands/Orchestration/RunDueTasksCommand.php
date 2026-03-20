<?php

namespace App\Console\Commands\Orchestration;

use App\Services\Orchestration\ScheduledTaskRunner;
use Illuminate\Console\Command;

class RunDueTasksCommand extends Command
{
    protected $signature = 'orchestration:run-due-tasks';
    protected $description = 'Run all scheduled tasks that are due';

    public function handle(ScheduledTaskRunner $runner): int
    {
        $this->info('Checking for due scheduled tasks...');

        $count = $runner->runDueTasks();

        if ($count === 0) {
            $this->info('No tasks are due at this time.');
        } else {
            $this->info("Executed {$count} scheduled task(s).");
        }

        return 0;
    }
}
