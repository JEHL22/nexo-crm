<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationUpdateSimDetail extends Model
{
    protected $fillable = [
        'validation_update_id',
        'line_number',
        'serial_number',
        'sim_number',
    ];

    public function validationUpdate(): BelongsTo
    {
        return $this->belongsTo(ValidationUpdate::class);
    }
}
