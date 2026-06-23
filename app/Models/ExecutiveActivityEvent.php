<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutiveActivityEvent extends Model
{
    protected $fillable = [
        'session_id',
        'executive_user_id',
        'supervisor_user_id',
        'event_type',
        'module_name',
        'route_name',
        'page_url',
        'label',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExecutiveActivitySession::class, 'session_id');
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
