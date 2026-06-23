<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderNotification extends Model
{
    protected $fillable = [
        'user_id',
        'interaction_id',
        'lead_id',
        'lead_label',
        'title',
        'message',
        'reminder_stage',
        'scheduled_for',
        'notified_at',
        'read_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'notified_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
