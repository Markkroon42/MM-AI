<?php

namespace App\Jobs\Reporting;

use App\Services\Reporting\KpiSnapshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateKpiSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?\Carbon\Carbon $date = null
    ) {}

    public function handle(KpiSnapshotService $kpiSnapshotService): void
    {
        Log::info('[CREATE_KPI_SNAPSHOT_JOB] Starting KPI snapshot creation');

        try {
            $snapshot = $kpiSnapshotService->createDailySnapshot($this->date);

            Log::info('[CREATE_KPI_SNAPSHOT_JOB] KPI snapshot created successfully', [
                'snapshot_id' => $snapshot->id,
                'snapshot_date' => $snapshot->snapshot_date,
            ]);
        } catch (\Exception $e) {
            Log::error('[CREATE_KPI_SNAPSHOT_JOB] KPI snapshot creation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
