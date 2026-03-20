<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTask;
use App\Services\Orchestration\ScheduledTaskRunner;
use App\Services\Orchestration\ScheduledTaskService;
use Illuminate\Http\Request;

class ScheduledTaskController extends Controller
{
    public function __construct(
        protected ScheduledTaskService $taskService,
        protected ScheduledTaskRunner $taskRunner
    ) {}

    public function index()
    {
        $tasks = ScheduledTask::with('latestRun')
            ->orderBy('status', 'asc')
            ->orderBy('next_run_at', 'asc')
            ->paginate(20);

        return view('admin.scheduled-tasks.index', compact('tasks'));
    }

    public function show(ScheduledTask $task)
    {
        $task->load(['runs' => function ($query) {
            $query->orderBy('started_at', 'desc')->limit(20);
        }]);

        return view('admin.scheduled-tasks.show', compact('task'));
    }

    public function create()
    {
        return view('admin.scheduled-tasks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'task_type' => 'required|string|in:run_agent,generate_report,create_kpi_snapshot,sync_meta,cleanup_old_data',
            'description' => 'nullable|string',
            'cron_expression' => 'required|string',
            'run_context_json' => 'nullable|json',
            'status' => 'required|in:active,paused,disabled',
            'alert_on_failure' => 'boolean',
        ]);

        $validated['run_context_json'] = json_decode($validated['run_context_json'] ?? '{}', true);

        $task = $this->taskService->create($validated);

        return redirect()->route('admin.scheduled-tasks.show', $task)
            ->with('success', 'Scheduled task created successfully.');
    }

    public function edit(ScheduledTask $task)
    {
        return view('admin.scheduled-tasks.edit', compact('task'));
    }

    public function update(Request $request, ScheduledTask $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'task_type' => 'required|string|in:run_agent,generate_report,create_kpi_snapshot,sync_meta,cleanup_old_data',
            'description' => 'nullable|string',
            'cron_expression' => 'required|string',
            'run_context_json' => 'nullable|json',
            'status' => 'required|in:active,paused,disabled',
            'alert_on_failure' => 'boolean',
        ]);

        $validated['run_context_json'] = json_decode($validated['run_context_json'] ?? '{}', true);

        $this->taskService->update($task, $validated);

        return redirect()->route('admin.scheduled-tasks.show', $task)
            ->with('success', 'Scheduled task updated successfully.');
    }

    public function runNow(ScheduledTask $task)
    {
        try {
            $run = $this->taskRunner->run($task);

            if ($run->isSuccessful()) {
                return redirect()->route('admin.scheduled-tasks.show', $task)
                    ->with('success', 'Task executed successfully.');
            } else {
                return redirect()->route('admin.scheduled-tasks.show', $task)
                    ->with('error', "Task execution failed: {$run->error_message}");
            }
        } catch (\Exception $e) {
            return redirect()->route('admin.scheduled-tasks.show', $task)
                ->with('error', "Task execution failed: {$e->getMessage()}");
        }
    }

    public function pause(ScheduledTask $task)
    {
        $this->taskService->pause($task);

        return redirect()->route('admin.scheduled-tasks.show', $task)
            ->with('success', 'Task paused successfully.');
    }

    public function resume(ScheduledTask $task)
    {
        $this->taskService->resume($task);

        return redirect()->route('admin.scheduled-tasks.show', $task)
            ->with('success', 'Task resumed successfully.');
    }
}
