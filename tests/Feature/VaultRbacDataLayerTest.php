<?php

declare(strict_types=1);

namespace Tests\Feature;

use Artwallet\VaultRbac\Casts\ValidatedJsonArray;
use Artwallet\VaultRbac\Contracts\PermissionCacheVersionRepository;
use Artwallet\VaultRbac\Contracts\RoleRepository;
use Artwallet\VaultRbac\Contracts\TenantRepository;
use Artwallet\VaultRbac\Enums\CacheBumpScope;
use Artwallet\VaultRbac\Enums\TenantStatus;
use Artwallet\VaultRbac\Exceptions\Data\CastTransformationException;
use Artwallet\VaultRbac\Exceptions\Data\DuplicateEntityException;
use Artwallet\VaultRbac\Exceptions\Data\EntityNotFoundException;
use Artwallet\VaultRbac\Exceptions\Data\UnsupportedIdentifierTypeException;
use Artwallet\VaultRbac\Models\Tenant;
use Artwallet\VaultRbac\Models\TenantRole;
use Artwallet\VaultRbac\Support\PrimaryKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VaultRbacDataLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_repository_get_throws_entity_not_found(): void
    {
        $this->expectException(EntityNotFoundException::class);

        app(RoleRepository::class)->getById(9_999_999);
    }

    public function test_permission_cache_version_bump_is_monotonic(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'cache-tenant',
            'name' => 'Cache tenant',
            'status' => TenantStatus::Active->value,
        ]);

        $repo = app(PermissionCacheVersionRepository::class);

        $this->assertSame(0, $repo->getVersion($tenant->id, CacheBumpScope::Subjects->value));
        $this->assertSame(1, $repo->bump($tenant->id, CacheBumpScope::Subjects->value));
        $this->assertSame(2, $repo->bump($tenant->id, CacheBumpScope::Subjects->value));
        $this->assertSame(2, $repo->getVersion($tenant->id, CacheBumpScope::Subjects->value));
    }

    public function test_tenant_repository_persist_duplicate_slug_throws_duplicate_entity(): void
    {
        $repo = app(TenantRepository::class);

        $a = new Tenant([
            'slug' => 'dup-slug',
            'name' => 'A',
            'status' => TenantStatus::Active,
        ]);
        $repo->persist($a);

        $b = new Tenant([
            'slug' => 'dup-slug',
            'name' => 'B',
            'status' => TenantStatus::Active,
        ]);

        $this->expectException(DuplicateEntityException::class);
        $repo->persist($b);
    }

    public function test_primary_key_rejects_invalid_bigint(): void
    {
        $this->expectException(UnsupportedIdentifierTypeException::class);
        PrimaryKey::normalize('not-numeric');
    }

    public function test_validated_json_array_cast_throws_on_malformed_json(): void
    {
        $cast = new ValidatedJsonArray;
        $model = new TenantRole;

        $this->expectException(CastTransformationException::class);
        $cast->get($model, 'metadata', '{invalid', []);
    }

    public function test_validated_json_array_accepts_null_metadata(): void
    {
        $cast = new ValidatedJsonArray;
        $model = new TenantRole;
        $this->assertNull($cast->get($model, 'metadata', null, []));
    }
}
