<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleSupervisorHistory extends Model
{
    protected $fillable = [
        'sale_id',
        'user_id',
        'action',
        'changed_fields',
        'notes',
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
