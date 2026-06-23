<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSisacData extends Model
{
    protected $fillable = [
        'lead_id',
        'semaforo',
        'resultado',
        'cantidad_lineas_ofrecer',
        'deposito_garantia',
        'rango_lc_disponible',
    ];

    protected $casts = [
        'cantidad_lineas_ofrecer' => 'integer',
        'deposito_garantia' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
