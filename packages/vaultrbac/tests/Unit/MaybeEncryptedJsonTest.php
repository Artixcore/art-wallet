<?php

declare(strict_types=1);

namespace Artwallet\VaultRbac\Tests\Unit;

use Artwallet\VaultRbac\Exceptions\Data\InvalidEncryptedPayloadException;
use Artwallet\VaultRbac\Models\Permission;
use Artwallet\VaultRbac\Models\Tenant;
use Artwallet\VaultRbac\Tests\TestCase;
use Illuminate\Support\Facades\DB;

final class MaybeEncryptedJsonTest extends TestCase
{
    public function test_round_trip_plaintext_metadata_when_encryption_disabled(): void
    {
        config(['vaultrbac.encryption.metadata.enabled' => false]);

        $tenant = Tenant::query()->create([
            'slug' => 't-'.uniqid(),
            'name' => 'T',
            'status' => 'active',
        ]);

        $p = Permission::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'p.'.uniqid(),
            'metadata' => ['k' => 'v'],
        ]);

        $fresh = Permission::query()->findOrFail($p->getKey());
        self::assertSame(['k' => 'v'], $fresh->metadata);
    }

    public function test_invalid_json_throws_invalid_encrypted_payload_exception(): void
    {
        config(['vaultrbac.encryption.metadata.enabled' => false]);

        $tenant = Tenant::query()->create([
            'slug' => 't2-'.uniqid(),
            'name' => 'T2',
            'status' => 'active',
        ]);

        $name = 'p2.'.uniqid();
        Permission::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => $name,
            'metadata' => ['ok' => true],
        ]);

        $table = config('vaultrbac.tables.permissions');
        DB::table($table)->where('name', $name)->update(['metadata' => '{not-json']);

        $this->expectException(InvalidEncryptedPayloadException::class);

        Permission::query()->where('name', $name)->first()?->metadata;
    }
}
