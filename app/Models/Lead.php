<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    // source — origen del lead
    public const SOURCE_MY_BASE = 'mi_base';

    public const SOURCE_BULK_IMPORT = 'carga_masiva';

    public const SOURCE_COMPANY = 'empresa';

    // delivery_status — ciclo de asignación
    public const DELIVERY_AVAILABLE = 'disponible';

    public const DELIVERY_ASSIGNED = 'asignado';

    public const DELIVERY_MANAGED = 'gestionado';

    // status_general
    public const GENERAL_CONTACTED = 'contactado';

    public const GENERAL_NOT_CONTACTED = 'no_contactado';

    // status_specific (compartido con Interaction.status_specific)
    public const SPECIFIC_RESCHEDULED = 'reprogramado';

    public const SPECIFIC_NEGOTIATION = 'negociacion';

    public const SPECIFIC_NOT_INTERESTED = 'no_desea';

    public const SPECIFIC_AGREEMENT_ACCEPTED = 'acuerdo_aceptado';

    public const SPECIFIC_NO_ANSWER = 'no_contesta';

    public const SPECIFIC_PHONE_OFF = 'telefono_apagado';

    public const SPECIFIC_NOT_EXISTS = 'no_existe';

    // status_final — resumen del lead
    public const FINAL_NO_MANAGEMENT = 'sin_gestion';

    public const FINAL_IN_FOLLOW_UP = 'en_seguimiento';

    public const FINAL_CLOSED_NO_SALE = 'cerrado_sin_venta';

    public const FINAL_AGREEMENT_ACCEPTED = 'acuerdo_aceptado';

    protected $fillable = [
        'campaign_id',
        'assigned_to_user_id',
        'supervisor_user_id',
        'created_by_user_id',

        'source',
        'delivery_status',
        'taken_at',
        'released_at',
        'disabled_at',
        'disabled_reason',
        'no_contact_attempts',

        'full_name',
        'ruc',
        'business_name',
        'representative_name',
        'dni',
        'fiscal_address',
        'current_operator',
        'current_line_count',
        'segment',
        'max_speed',
        'package',
        'technology',
        'last_contact_name',
        'last_contact_phone',
        'status_general',
        'status_specific',
        'status_final',
        'product_type_offered',
        'offered_line_count',
        'monthly_payment',
        'call_summary',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'released_at' => 'datetime',
        'disabled_at' => 'datetime',
        'no_contact_attempts' => 'integer',
        'offered_line_count' => 'integer',
        'monthly_payment' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(LeadPhone::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    public function sisacData(): HasOne
    {
        return $this->hasOne(LeadSisacData::class);
    }

    public function workSessions(): HasMany
    {
        return $this->hasMany(LeadWorkSession::class);
    }
}
