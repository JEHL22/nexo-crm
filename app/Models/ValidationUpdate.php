<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidationUpdate extends Model
{
    protected $fillable = [
        'sale_id',
        'user_id',
        'sisac_status',
        'feedback',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function simDetails(): HasMany
    {
        return $this->hasMany(ValidationUpdateSimDetail::class)->orderBy('line_number');
    }
}
