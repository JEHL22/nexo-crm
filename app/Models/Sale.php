<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /**
     * Estados del ciclo de vida de la venta. Única fuente de verdad:
     * los controllers deben referenciar estas constantes, no strings sueltos.
     */
    public const STATUS_ACCEPTED = 'acuerdo_aceptado';

    // management_status — flujo de Postventa
    public const MANAGEMENT_PENDING_SUPERVISION = 'pendiente_supervision';

    public const MANAGEMENT_PENDING_VALIDATION = 'pendiente_validacion';

    public const MANAGEMENT_APPROVED = 'aprobado';

    public const MANAGEMENT_REJECTED = 'rechazado';

    public const MANAGEMENT_OBSERVED = 'observado';

    /** Estados que Postventa puede asignar desde su módulo */
    public const MANAGEMENT_STATUSES = [
        self::MANAGEMENT_PENDING_VALIDATION,
        self::MANAGEMENT_APPROVED,
        self::MANAGEMENT_REJECTED,
        self::MANAGEMENT_OBSERVED,
    ];

    // sisac_status — flujo de Mesa de Control. Nace en pendiente_supervision
    // y Mesa de Control solo puede moverlo a los 4 estados de SISAC_STATUSES.
    public const SISAC_PENDING_SUPERVISION = 'pendiente_supervision';

    public const SISAC_IN_EVALUATION = 'en_evaluacion';

    public const SISAC_ACTIVE = 'activo';

    public const SISAC_REJECTED = 'rechazado';

    public const SISAC_DELIVERED = 'entregado';

    /** Estados que Mesa de Control puede asignar desde su módulo */
    public const SISAC_STATUSES = [
        self::SISAC_IN_EVALUATION,
        self::SISAC_ACTIVE,
        self::SISAC_REJECTED,
        self::SISAC_DELIVERED,
    ];

    // supervisor_validation_status — validación del supervisor previa a todo
    public const SUPERVISOR_VALIDATION_PENDING = 'pendiente';

    public const SUPERVISOR_VALIDATION_VALIDATED = 'validado';

    protected $fillable = [
        'lead_id',
        'interaction_id',
        'campaign_id',
        'executive_user_id',
        'supervisor_user_id',
        'status',
        'management_status',
        'sisac_status',
        'product_type',
        'offered_line_count',
        'monthly_payment',
        'customer_ruc',
        'customer_business_name',
        'customer_dni',
        'customer_representative_name',
        'customer_phone',
        'customer_address',
        'customer_coordinates',
        'plan_code',
        'approval_code',
        'customer_email',
        'service_channel',
        'attention_time_slot',
        'attention_date',
        'operator_name',
        'delivery_type',
        'fixed_agreement_supports',
        'tmo_to_agreement_seconds',
        'products_snapshot',
        'portability_phone_numbers_snapshot',
        'attachment_paths',
        'supervisor_validation_status',
        'supervisor_validated_at',
        'accepted_at',
        'closed_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
        'supervisor_validated_at' => 'datetime',
        'attention_date' => 'date',
        'tmo_to_agreement_seconds' => 'integer',
        'monthly_payment' => 'decimal:2',
        'fixed_agreement_supports' => 'array',
        'products_snapshot' => 'array',
        'portability_phone_numbers_snapshot' => 'array',
        'attachment_paths' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function postSaleUpdates(): HasMany
    {
        return $this->hasMany(PostSaleUpdate::class);
    }

    public function validationUpdates(): HasMany
    {
        return $this->hasMany(ValidationUpdate::class);
    }

    public function simDetails(): HasMany
    {
        return $this->hasMany(SaleSimDetail::class)->orderBy('line_number');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(SaleSupervisorHistory::class)->latest();
    }
}
