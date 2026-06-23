<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritorialProvinceSetting extends Model
{
    protected $fillable = [
        'province_id',
        'province_name',
        'status',
        'closing_time',
        'visit_time',
        'delivery_time',
        'selected_district_ids',
        'updated_by',
    ];

    protected $casts = [
        'selected_district_ids' => 'array',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
