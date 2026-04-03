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

        Schema::create($permissions, function (Blueprint $table) use ($tenants) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained($tenants)->cascadeOnDelete();
            $table->string('name');
            $table->string('permission_group', 191)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_wildcard_parent')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('permissions'));
    }
};
