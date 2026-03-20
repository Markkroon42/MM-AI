<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Execution\ExecutePublishJob;
use App\Models\PublishJob;
use Illuminate\Http\Request;

class PublishJobController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index(Request $request)
    {
        $query = PublishJob::with('draft')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        $jobs = $query->paginate(15);

        return view('admin.publish-jobs.index', compact('jobs'));
    }

    public function show(PublishJob $job)
    {
        $job->load('draft');

        return view('admin.publish-jobs.show', compact('job'));
    }

    public function retry(PublishJob $job)
    {
        if ($job->status !== 'failed') {
            return back()->with('error', 'Only failed jobs can be retried.');
        }

        // Reset job status
        $job->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        // Dispatch job again
        ExecutePublishJob::dispatch($job);

        return back()->with('success', 'Job retry initiated.');
    }
}
