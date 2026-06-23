<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExecutiveActivitySession extends Model
{
    protected $fillable = [
        'executive_user_id',
        'supervisor_user_id',
        'campaign_id',
        'login_at',
        'last_seen_at',
        'logout_at',
        'current_module_name',
        'current_route_name',
        'current_page_url',
        'current_page_entered_at',
        'is_crm_focused',
        'last_focus_change_at',
        'total_blurred_seconds',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'logout_at' => 'datetime',
        'current_page_entered_at' => 'datetime',
        'is_crm_focused' => 'boolean',
        'last_focus_change_at' => 'datetime',
        'total_blurred_seconds' => 'integer',
    ];

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExecutiveActivityEvent::class, 'session_id');
    }

    public function getTotalSessionSecondsAttribute(): int
    {
        $end = $this->logout_at ?: $this->last_seen_at;

        if (!$this->login_at || !$end) {
            return 0;
        }

        return max(0, $this->login_at->diffInSeconds($end));
    }
}
