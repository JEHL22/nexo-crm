<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractionOffer extends Model
{
    protected $fillable = [
        'interaction_id',
        'product_type',
        'mobile_mode',
        'portability_lines',
        'portability_monthly',
        'portability_promotion_name',
        'new_lines',
        'new_monthly',
        'new_promotion_name',
        'internet_speed',
        'fixed_monthly',
    ];

    protected $casts = [
        'portability_lines' => 'integer',
        'portability_monthly' => 'decimal:2',
        'new_lines' => 'integer',
        'new_monthly' => 'decimal:2',
        'fixed_monthly' => 'decimal:2',
    ];

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }
}
