<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiPromptConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'agent_type',
        'model',
        'temperature',
        'max_tokens',
        'system_prompt',
        'user_prompt_template',
        'response_format',
        'is_active',
    ];

    protected $casts = [
        'response_format' => 'array',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'is_active' => 'boolean',
    ];

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class, 'prompt_config_id');
    }
}
