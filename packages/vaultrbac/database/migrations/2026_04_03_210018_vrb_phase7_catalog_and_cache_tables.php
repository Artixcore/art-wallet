<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenants = VaultrbacTables::name('tenants');
        $roles = VaultrbacTables::name('roles');
        $permissions = VaultrbacTables::name('permissions');

        $tenantRoles = VaultrbacTables::name('tenant_roles');
        Schema::create($tenantRoles, function (Blueprint $table) use ($tenants, $roles) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'role_id']);
            $table->index(['tenant_id', 'is_enabled']);
        });

        $tenantPerms = VaultrbacTables::name('tenant_permissions');
        Schema::create($tenantPerms, function (Blueprint $table) use ($tenants, $permissions) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'permission_id']);
            $table->index(['tenant_id', 'is_enabled']);
        });

        $temporary = VaultrbacTables::name('temporary_permissions');
        $approvals = VaultrbacTables::name('approval_requests');
        $encrypted = VaultrbacTables::name('encrypted_metadata');
        Schema::create($temporary, function (Blueprint $table) use ($tenants, $permissions, $approvals, $encrypted) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->unsignedBigInteger('granted_by')->nullable()->index();
            $table->timestamp('valid_from')->useCurrent();
            $table->timestamp('valid_until');
            $table->string('reason', 191)->nullable();
            $table->foreignId('approval_request_id')->nullable()->constrained($approvals)->nullOnDelete();
            $table->foreignId('metadata_json_id')->nullable()->constrained($encrypted)->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'model_type', 'model_id', 'valid_until'], 'vrb_temp_perm_subject_until_idx');
            $table->index(['tenant_id', 'valid_until'], 'vrb_temp_perm_tenant_until_idx');
        });

        $expirations = VaultrbacTables::name('role_expirations');
        Schema::create($expirations, function (Blueprint $table) use ($tenants) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->string('target', 32);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('expires_at');
            $table->string('reason', 191)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'expires_at'], 'vrb_role_exp_tenant_until_idx');
            $table->index(['target', 'target_id'], 'vrb_role_exp_target_idx');
        });

        $cacheVersions = VaultrbacTables::name('permission_cache_versions');
        Schema::create($cacheVersions, function (Blueprint $table) use ($tenants) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->string('scope', 64);
            $table->string('subject_type', 191)->default('');
            $table->unsignedBigInteger('subject_id')->default(0);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['tenant_id', 'scope', 'subject_type', 'subject_id'], 'vrb_perm_cache_ver_subject_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('permission_cache_versions'));
        Schema::dropIfExists(VaultrbacTables::name('role_expirations'));
        Schema::dropIfExists(VaultrbacTables::name('temporary_permissions'));
        Schema::dropIfExists(VaultrbacTables::name('tenant_permissions'));
        Schema::dropIfExists(VaultrbacTables::name('tenant_roles'));
    }
};
