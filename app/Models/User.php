<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Notifications\Models\UserNotificationPreference;
use Artixcore\ArtGate\Traits\HasArtGateRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasArtGateRoles, HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * @return HasMany<Wallet, $this>
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * @return HasMany<ApiDevice, $this>
     */
    public function apiDevices(): HasMany
    {
        return $this->hasMany(ApiDevice::class);
    }

    /**
     * @return HasMany<LoginTrustedDevice, $this>
     */
    public function loginTrustedDevices(): HasMany
    {
        return $this->hasMany(LoginTrustedDevice::class);
    }

    /**
     * @return HasOne<BackupState, $this>
     */
    public function backupState(): HasOne
    {
        return $this->hasOne(BackupState::class);
    }

    /**
     * @return HasOne<RecoveryKit, $this>
     */
    public function recoveryKit(): HasOne
    {
        return $this->hasOne(RecoveryKit::class);
    }

    /**
     * @return HasMany<InAppNotification, $this>
     */
    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class);
    }

    /**
     * @return HasMany<UserNotificationPreference, $this>
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }

    /**
     * @return HasOne<UserSetting, $this>
     */
    public function userSetting(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * @return HasOne<UserSecurityPolicy, $this>
     */
    public function userSecurityPolicy(): HasOne
    {
        return $this->hasOne(UserSecurityPolicy::class);
    }

    /**
     * @return HasOne<MessagingPrivacySetting, $this>
     */
    public function messagingPrivacySetting(): HasOne
    {
        return $this->hasOne(MessagingPrivacySetting::class);
    }

    /**
     * @return HasOne<RiskThresholdSetting, $this>
     */
    public function riskThresholdSetting(): HasOne
    {
        return $this->hasOne(RiskThresholdSetting::class);
    }

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
            'is_admin' => 'boolean',
        ];
    }
}
