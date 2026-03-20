<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutiveReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'period_start',
        'period_end',
        'status',
        'headline_metrics_json',
        'highlights_json',
        'top_performers_json',
        'bottom_performers_json',
        'issues_json',
        'priorities_json',
        'executive_summary',
        'generated_at',
        'generation_duration_seconds',
        'error_message',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'headline_metrics_json' => 'array',
        'highlights_json' => 'array',
        'top_performers_json' => 'array',
        'bottom_performers_json' => 'array',
        'issues_json' => 'array',
        'priorities_json' => 'array',
        'generated_at' => 'datetime',
    ];

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isGenerating(): bool
    {
        return $this->status === 'generating';
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->period_start->isSameDay($this->period_end)) {
            return $this->period_start->format('M j, Y');
        }

        return $this->period_start->format('M j') . ' - ' . $this->period_end->format('M j, Y');
    }
}
