<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduledTaskRun;

class ScheduledTaskRunController extends Controller
{
    public function index()
    {
        $runs = ScheduledTaskRun::with('scheduledTask')
            ->orderBy('started_at', 'desc')
            ->paginate(50);

        return view('admin.scheduled-task-runs.index', compact('runs'));
    }

    public function show(ScheduledTaskRun $run)
    {
        $run->load('scheduledTask');

        return view('admin.scheduled-task-runs.show', compact('run'));
    }
}
