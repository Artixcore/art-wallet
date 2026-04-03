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
        $approvals = VaultrbacTables::name('approval_requests');

        Schema::create($approvals, function (Blueprint $table) use ($tenants) {
            $table->id();
            $table->foreignId('tenant_id')->constrained($tenants)->cascadeOnDelete();
            $table->unsignedBigInteger('requester_id')->nullable()->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->longText('payload')->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('required_approvers')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable()->index();
            $table->timestamp('decided_at')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('approval_requests'));
    }
};
