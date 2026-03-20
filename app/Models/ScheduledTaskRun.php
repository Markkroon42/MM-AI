<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledTaskRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_task_id',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'result_summary',
        'result_data_json',
        'error_message',
        'stack_trace',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'result_data_json' => 'array',
    ];

    public function scheduledTask(): BelongsTo
    {
        return $this->belongsTo(ScheduledTask::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && empty($this->error_message);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
