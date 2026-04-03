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
        $teams = VaultrbacTables::name('teams');
        $permissions = VaultrbacTables::name('permissions');
        $approvals = VaultrbacTables::name('approval_requests');
        $modelPermissions = VaultrbacTables::name('model_permissions');

        Schema::create($modelPermissions, function (Blueprint $table) use ($tenants, $teams, $permissions, $approvals) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained($teams)->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('effect', 16)->default('allow');
            $table->unsignedBigInteger('assigned_by')->nullable()->index();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('suspended_at')->nullable()->index();
            $table->foreignId('approval_request_id')->nullable()->constrained($approvals)->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'model_type', 'model_id']);
            $table->index(['permission_id']);
            $table->index(['effect']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('model_permissions'));
    }
};
