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
        $name = VaultrbacTables::name('audit_events');

        Schema::create($name, function (Blueprint $table) {
            $table->id();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('action', 191);
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->json('diff')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id', 191)->nullable();
            $table->string('device_id', 191)->nullable();
            $table->string('request_id', 128)->nullable()->index();
            $table->string('prev_hash', 128)->nullable();
            $table->string('row_hash', 128)->nullable()->index();
            $table->text('signature')->nullable();
            $table->boolean('immutable')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('audit_events'));
    }
};
