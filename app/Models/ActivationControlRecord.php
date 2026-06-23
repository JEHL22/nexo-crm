<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivationControlRecord extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'updated_by_user_id',
        'empresa',
        'mes',
        'fecha_ingreso',
        'fecha_activacion',
        'sec',
        'py',
        'sot',
        'linea',
        'large',
        'cliente',
        'ruc',
        'servicio',
        'tipo_cliente',
        'plan_tarifario',
        'porcentaje_dscto',
        'ajuste',
        'cf',
        'adic',
        'sva',
        'cf_sin_igv',
        'q',
        'material',
        'marca',
        'consultor',
        'modalidad',
        'estado',
        'comentario',
        'score',
        'segmento',
        'opotunidad',
        'estado_sf',
        'f_cierre_op',
        'f_liberacion',
        'validacion',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_activacion' => 'date',
        'porcentaje_dscto' => 'decimal:2',
        'cf' => 'decimal:2',
        'adic' => 'decimal:2',
        'sva' => 'decimal:2',
        'cf_sin_igv' => 'decimal:2',
        'q' => 'integer',
        'score' => 'integer',
        'f_cierre_op' => 'date',
        'f_liberacion' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
