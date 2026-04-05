<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('onboarding_status', 48)->default('completed');
            $table->timestamp('onboarding_completed_at')->nullable();
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('wallet_vault_ciphertext');
        });

        Schema::create('onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('state', 48);
            $table->string('step_token_hash', 64);
            $table->timestamp('step_token_expires_at');
            $table->unsignedSmallInteger('passphrase_attempts')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->json('challenge_indices')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['state', 'step_token_expires_at']);
        });

        Schema::create('onboarding_passphrase_verifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('verifier_salt_hex', 64);
            $table->string('verifier_hmac_hex', 64)->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        DB::table('users')->update([
            'onboarding_status' => 'completed',
            'onboarding_completed_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_passphrase_verifiers');
        Schema::dropIfExists('onboarding_sessions');

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'onboarding_status', 'onboarding_completed_at']);
        });
    }
};
