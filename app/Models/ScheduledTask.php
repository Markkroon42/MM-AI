<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'task_type',
        'description',
        'cron_expression',
        'run_context_json',
        'status',
        'next_run_at',
        'last_run_at',
        'run_count',
        'failure_count',
        'alert_on_failure',
    ];

    protected $casts = [
        'run_context_json' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'alert_on_failure' => 'boolean',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(ScheduledTaskRun::class);
    }

    public function latestRun()
    {
        return $this->hasOne(ScheduledTaskRun::class)->latestOfMany();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDue(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->next_run_at === null) {
            return false;
        }

        return $this->next_run_at->isPast();
    }

    public function getHealthStatusAttribute(): string
    {
        if ($this->status !== 'active') {
            return 'inactive';
        }

        if ($this->failure_count >= 3) {
            return 'unhealthy';
        }

        if ($this->failure_count > 0) {
            return 'degraded';
        }

        return 'healthy';
    }
}
