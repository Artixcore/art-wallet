<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('public_key', 128)->unique();
            $table->string('key_alg', 32)->default('ed25519');
            $table->unsignedInteger('trust_version')->default(1);
            $table->text('device_label_ciphertext')->nullable();
            $table->json('fingerprint_signals_json')->nullable();
            $table->timestamp('trusted_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_trusted_devices');
    }
};
