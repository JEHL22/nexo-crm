<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalMessage extends Model
{
    protected $fillable = [
        'sender_user_id',
        'title',
        'message',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(InternalMessageRecipient::class);
    }
}
