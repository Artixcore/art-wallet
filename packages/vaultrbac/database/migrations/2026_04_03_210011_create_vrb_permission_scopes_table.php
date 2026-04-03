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
        $permissions = VaultrbacTables::name('permissions');
        $scopes = VaultrbacTables::name('permission_scopes');

        Schema::create($scopes, function (Blueprint $table) use ($tenants, $permissions) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->string('scope_type', 32);
            $table->string('scope_model_type')->nullable();
            $table->unsignedBigInteger('scope_model_id')->nullable();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'scope_model_type', 'scope_model_id'], 'vrb_perm_scope_subject_idx');
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('permission_scopes'));
    }
};
