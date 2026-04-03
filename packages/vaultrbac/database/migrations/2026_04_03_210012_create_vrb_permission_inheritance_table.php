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
        $inheritance = VaultrbacTables::name('permission_inheritance');

        Schema::create($inheritance, function (Blueprint $table) use ($tenants, $permissions) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained($tenants)->cascadeOnDelete();
            $table->foreignId('ancestor_permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->foreignId('descendant_permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'ancestor_permission_id', 'descendant_permission_id'],
                'vrb_permission_inheritance_uq',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('permission_inheritance'));
    }
};
