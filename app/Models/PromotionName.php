<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionName extends Model
{
    protected $fillable = [
        'sender_user_id',
        'name',
        'monthly_price',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
