<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_challenges', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('login_trusted_device_id')->nullable()->constrained('login_trusted_devices')->nullOnDelete();
            $table->string('challenge_nonce', 128)->unique();
            $table->string('client_code', 16);
            $table->string('session_binding_hash', 128);
            $table->string('purpose', 32);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->text('signature')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'expires_at']);
            $table->index(['user_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_challenges');
    }
};
