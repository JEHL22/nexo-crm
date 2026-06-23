<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrSurvey extends Model
{
    protected $fillable = [
        'sender_user_id',
        'title',
        'prompt',
        'response_type',
        'options_json',
        'detail_placeholder',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(HrSurveyRecipient::class);
    }
}
