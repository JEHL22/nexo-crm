<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadWorkSession extends Model
{
    protected $fillable = [
        'lead_id',
        'campaign_id',
        'executive_user_id',
        'supervisor_user_id',
        'module_name',
        'route_name',
        'started_at',
        'last_heartbeat_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function getDurationSecondsAttribute(): int
    {
        $end = $this->ended_at ?: $this->last_heartbeat_at;

        if (!$this->started_at || !$end) {
            return 0;
        }

        return max(0, $this->started_at->diffInSeconds($end));
    }
}
