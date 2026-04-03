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
        $conditions = VaultrbacTables::name('permission_conditions');

        Schema::create($conditions, function (Blueprint $table) use ($tenants) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained($tenants)->cascadeOnDelete();
            $table->string('name');
            $table->json('expression');
            $table->string('evaluator_key', 128);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('permission_conditions'));
    }
};
