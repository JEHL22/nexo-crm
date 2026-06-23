<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'crm_primary_color',
        'crm_secondary_color',
        'crm_theme_mode',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function crmPrimaryColor(): string
    {
        return $this->isValidThemeColor($this->crm_primary_color)
            ? $this->crm_primary_color
            : '#DA291C';
    }

    public function crmSecondaryColor(): string
    {
        return $this->isValidThemeColor($this->crm_secondary_color)
            ? $this->crm_secondary_color
            : '#1F2937';
    }

    public function crmThemeMode(): string
    {
        return in_array($this->crm_theme_mode, ['light', 'dark'], true)
            ? $this->crm_theme_mode
            : 'light';
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo_path) {
            return null;
        }

        return route('profile.photo.show', [
            'user' => $this->getKey(),
            'v' => optional($this->updated_at)->timestamp ?? time(),
        ]);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    protected function isValidThemeColor(?string $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }

    public function assignedLeads()
    {
        return $this->hasMany(Lead::class, 'assigned_to_user_id');
    }

    public function supervisedLeads()
    {
        return $this->hasMany(Lead::class, 'supervisor_user_id');
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function reminderNotifications()
    {
        return $this->hasMany(ReminderNotification::class);
    }

    public function executiveSales()
    {
        return $this->hasMany(Sale::class, 'executive_user_id');
    }

    public function supervisedSales()
    {
        return $this->hasMany(Sale::class, 'supervisor_user_id');
    }

    public function workSessions()
    {
        return $this->hasMany(LeadWorkSession::class, 'executive_user_id');
    }

    public function sentInternalMessages()
    {
        return $this->hasMany(InternalMessage::class, 'sender_user_id');
    }

    public function internalMessageRecipients()
    {
        return $this->hasMany(InternalMessageRecipient::class);
    }

    public function hrSurveys()
    {
        return $this->hasMany(HrSurvey::class, 'sender_user_id');
    }

    public function hrSurveyRecipients()
    {
        return $this->hasMany(HrSurveyRecipient::class);
    }

    public function marketingPhrases()
    {
        return $this->hasMany(MarketingPhrase::class, 'sender_user_id');
    }
}
