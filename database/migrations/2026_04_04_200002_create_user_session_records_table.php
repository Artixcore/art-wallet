<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_session_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id_hash', 128)->unique();
            $table->foreignId('login_trusted_device_id')->nullable()->constrained('login_trusted_devices')->nullOnDelete();
            $table->string('ip_hash', 128)->nullable();
            $table->string('user_agent_hash', 128)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->index(['user_id', 'last_seen_at']);
            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_session_records');
    }
};
