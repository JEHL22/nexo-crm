<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrSurveyRecipient extends Model
{
    protected $fillable = [
        'hr_survey_id',
        'user_id',
        'displayed_at',
        'answered_at',
        'selected_option',
        'answer_detail',
    ];

    protected function casts(): array
    {
        return [
            'displayed_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(HrSurvey::class, 'hr_survey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
