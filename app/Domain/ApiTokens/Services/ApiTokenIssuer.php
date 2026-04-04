<?php

declare(strict_types=1);

namespace App\Domain\ApiTokens\Services;

use App\Domain\ApiTokens\Exceptions\RefreshTokenReuseException;
use App\Models\ApiDevice;
use App\Models\ApiRefreshToken;
use App\Models\SanctumPersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ApiTokenIssuer
{
    public function __construct(
        private readonly int $accessTtlMinutes,
        private readonly int $refreshTtlDays,
    ) {}

    /**
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int, token_type: 'Bearer', device: ApiDevice}
     */
    public function issueWithPassword(User $user, string $password, string $deviceId, ?string $deviceName, ?string $platform): array
    {
        if (! Hash::check($password, $user->password)) {
            throw new \InvalidArgumentException(__('Invalid credentials.'));
        }

        return DB::transaction(function () use ($user, $deviceId, $deviceName, $platform): array {
            $device = ApiDevice::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $deviceId,
                ],
                [
                    'name' => $deviceName,
                    'platform' => $platform,
                    'last_used_at' => now(),
                ],
            );

            return $this->rotateTokens($user, $device);
        });
    }

    /**
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int, token_type: 'Bearer', device: ApiDevice}
     */
    public function rotateWithRefreshToken(string $plainRefresh, string $deviceId): array
    {
        $hash = hash('sha256', $plainRefresh);

        /** @var ApiRefreshToken|null $row */
        $row = ApiRefreshToken::query()->where('token_hash', $hash)->first();

        if ($row === null) {
            throw new \InvalidArgumentException(__('Invalid or expired refresh token.'));
        }

        if ($row->revoked_at !== null) {
            $this->revokeFamilyId($row->family_id);

            throw new RefreshTokenReuseException;
        }

        if ($row->expires_at->isPast()) {
            throw new \InvalidArgumentException(__('Invalid or expired refresh token.'));
        }

        $device = ApiDevice::query()
            ->whereKey($row->api_device_id)
            ->where('device_id', $deviceId)
            ->firstOrFail();

        $user = User::query()->findOrFail($row->user_id);

        return DB::transaction(function () use ($row, $user, $device): array {
            $this->markRefreshReplaced($row);

            return $this->rotateTokens($user, $device);
        });
    }

    public function revokeRefreshFamily(string $plainRefresh): void
    {
        $hash = hash('sha256', $plainRefresh);
        $row = ApiRefreshToken::query()->where('token_hash', $hash)->first();
        if ($row === null) {
            return;
        }

        $this->revokeFamilyId($row->family_id);
    }

    public function revokeFamilyId(string $familyId): void
    {
        $deviceIds = ApiRefreshToken::query()
            ->where('family_id', $familyId)
            ->pluck('api_device_id')
            ->unique()
            ->all();

        ApiRefreshToken::query()->where('family_id', $familyId)->update(['revoked_at' => now()]);

        if ($deviceIds !== []) {
            SanctumPersonalAccessToken::query()->whereIn('api_device_id', $deviceIds)->delete();
        }
    }

    private function markRefreshReplaced(ApiRefreshToken $row): void
    {
        $row->update(['revoked_at' => now()]);
    }

    /**
     * @return array{user: User, access_token: string, refresh_token: string, expires_in: int, token_type: 'Bearer', device: ApiDevice}
     */
    private function rotateTokens(User $user, ApiDevice $device): array
    {
        $familyId = (string) Str::uuid();

        SanctumPersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', $user->getMorphClass())
            ->where('api_device_id', $device->id)
            ->delete();

        $expiresAt = now()->addMinutes($this->accessTtlMinutes);
        $accessToken = $user->createToken(
            name: 'api-v1:'.$device->device_id,
            abilities: ['api:v1', 'device:'.$device->id],
            expiresAt: $expiresAt,
        );

        /** @var SanctumPersonalAccessToken $pat */
        $pat = $accessToken->accessToken;
        $pat->forceFill(['api_device_id' => $device->id])->save();

        $plainRefresh = Str::random(64);
        ApiRefreshToken::query()->create([
            'user_id' => $user->id,
            'api_device_id' => $device->id,
            'family_id' => $familyId,
            'token_hash' => hash('sha256', $plainRefresh),
            'expires_at' => now()->addDays($this->refreshTtlDays),
        ]);

        $device->update(['last_used_at' => now()]);

        return [
            'user' => $user,
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $plainRefresh,
            'expires_in' => $this->accessTtlMinutes * 60,
            'token_type' => 'Bearer',
            'device' => $device->fresh(),
        ];
    }
}
