<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_config_id',
        'agent_name',
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'model',
        'input_payload_json',
        'output_payload_json',
        'status',
        'tokens_input',
        'tokens_output',
        'cost_estimate',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'input_payload_json' => 'array',
        'output_payload_json' => 'array',
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'cost_estimate' => 'decimal:6',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function promptConfig(): BelongsTo
    {
        return $this->belongsTo(AiPromptConfig::class, 'prompt_config_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
