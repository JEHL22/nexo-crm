<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorStatusNotification extends Model
{
    protected $fillable = [
        'user_id',
        'sale_id',
        'validation_update_id',
        'previous_status',
        'current_status',
        'title',
        'message',
        'notified_at',
        'read_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function validationUpdate(): BelongsTo
    {
        return $this->belongsTo(ValidationUpdate::class);
    }
}
