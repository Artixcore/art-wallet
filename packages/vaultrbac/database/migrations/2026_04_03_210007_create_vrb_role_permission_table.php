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
        $conditions = VaultrbacTables::name('permission_conditions');
        $pivot = VaultrbacTables::name('role_permission');

        Schema::create($pivot, function (Blueprint $table) use ($tenants, $roles, $permissions, $conditions) {
            $table->id();
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained($tenants)->cascadeOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('source', 32)->default('direct');
            $table->foreignId('condition_id')->nullable()->constrained($conditions)->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'role_id']);
            $table->index(['permission_id']);
            $table->unique(['role_id', 'permission_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('role_permission'));
    }
};
