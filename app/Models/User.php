<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Artixcore\ArtGate\Traits\HasArtGateRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasArtGateRoles, HasFactory, Notifiable;

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
