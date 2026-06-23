<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleSimDetail extends Model
{
    protected $fillable = [
        'sale_id',
        'line_number',
        'serial_number',
        'sim_number',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
