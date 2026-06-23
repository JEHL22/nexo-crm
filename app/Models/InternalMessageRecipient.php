<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMessageRecipient extends Model
{
    protected $fillable = [
        'internal_message_id',
        'user_id',
        'displayed_at',
        'read_at',
    ];

    protected $casts = [
        'displayed_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function internalMessage(): BelongsTo
    {
        return $this->belongsTo(InternalMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
