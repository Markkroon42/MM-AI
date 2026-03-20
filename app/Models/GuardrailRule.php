<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardrailRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'applies_to_action_type',
        'condition_expression',
        'effect',
        'severity',
        'message_template',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Scope to get only active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get rules for specific action type
     */
    public function scopeForActionType($query, string $actionType)
    {
        return $query->where('applies_to_action_type', $actionType);
    }

    /**
     * Scope to order by priority (lower number = higher priority)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
