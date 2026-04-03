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

        Schema::create($roles, function (Blueprint $table) use ($tenants, $roles) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained($tenants)->cascadeOnDelete();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('parent_role_id')->nullable()->constrained($roles)->nullOnDelete();
            $table->boolean('is_system')->default(false);
            $table->string('activation_state', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->string('integrity_hash', 128)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'activation_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('roles'));
    }
};
