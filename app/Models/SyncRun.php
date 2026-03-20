<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'account_id',
        'sync_type',
        'status',
        'started_at',
        'finished_at',
        'records_processed',
        'error_message',
        'meta_json',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'records_processed' => 'integer',
        'meta_json' => 'array',
    ];

    public function getDurationAttribute(): ?float
    {
        if ($this->started_at && $this->finished_at) {
            return $this->started_at->diffInSeconds($this->finished_at);
        }

        return null;
    }
}
