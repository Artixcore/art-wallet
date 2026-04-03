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
        $hierarchy = VaultrbacTables::name('role_hierarchy');

        Schema::create($hierarchy, function (Blueprint $table) use ($tenants, $roles) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained($tenants)->cascadeOnDelete();
            $table->foreignId('child_role_id')->constrained($roles)->cascadeOnDelete();
            $table->foreignId('parent_role_id')->constrained($roles)->cascadeOnDelete();
            $table->unsignedInteger('depth_hint')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'child_role_id', 'parent_role_id'], 'vrb_role_hierarchy_edge_uq');
            $table->index('parent_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('role_hierarchy'));
    }
};
