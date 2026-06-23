<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisorExecutive extends Model
{
    protected $table = 'supervisor_executive';

    protected $fillable = [
        'supervisor_user_id',
        'executive_user_id',
        'campaign_id',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function executive(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executive_user_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}