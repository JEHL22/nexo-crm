<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerritorialProvinceSettingHistory extends Model
{
    protected $fillable = [
        'territorial_province_setting_id',
        'province_id',
        'province_name',
        'user_id',
        'action',
        'changed_fields',
    ];

    protected $casts = [
        'changed_fields' => 'array',
    ];

    public function setting(): BelongsTo
    {
        return $this->belongsTo(TerritorialProvinceSetting::class, 'territorial_province_setting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
