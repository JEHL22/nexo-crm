<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Interaction extends Model
{
    protected $fillable = [
        // CAMPOS OBLIGATORIOS POR BD
        'lead_id',
        'user_id',
        'campaign_id',
        'status',
        // CAMPOS DE ESTADO
        'interaction_type',
        'status_general',
        'status_specific',
        'product_type_offered',
        'offered_line_count',
        'monthly_payment',
        // DETALLE
        'call_detail',
        'next_contact_at',
        'is_agreement',
        'agreed_at',
        // DATOS DE LLAMADA
        'contact_name',
        'contact_phone',
    ];

    protected $casts = [
        'next_contact_at' => 'datetime',
        'agreed_at' => 'datetime',
        'is_agreement' => 'boolean',
        'offered_line_count' => 'integer',
        'monthly_payment' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(InteractionOffer::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }
}
