<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('locale', 16)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('theme', 16)->default('system');
            $table->json('ui_preferences_json')->nullable();
            $table->unsignedSmallInteger('ui_preferences_version')->default(1);
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::create('user_security_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('idle_timeout_minutes')->default(60);
            $table->unsignedSmallInteger('max_session_duration_minutes')->nullable();
            $table->boolean('notify_new_device_login')->default(true);
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::create('wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('default_fee_tier', 32)->nullable();
            $table->boolean('show_testnet_assets')->default(false);
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::create('wallet_transaction_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('confirm_above_amount', 20, 8)->nullable();
            $table->char('fiat_currency', 3)->default('USD');
            $table->boolean('require_second_approval')->default(false);
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::create('messaging_privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('read_receipts_enabled')->default(true);
            $table->boolean('typing_indicators_enabled')->default(true);
            $table->unsignedTinyInteger('max_attachment_mb')->default(10);
            $table->boolean('safety_warnings_enabled')->default(true);
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::create('risk_threshold_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('large_tx_alert_fiat', 20, 2)->nullable();
            $table->char('large_tx_alert_currency', 3)->default('USD');
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestamps();
        });

        Schema::create('settings_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 32);
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('setting_key', 128);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['wallet_id', 'created_at']);
        });

        Schema::create('settings_change_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->string('purpose', 64);
            $table->json('payload_json')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings_change_confirmations');
        Schema::dropIfExists('settings_change_logs');
        Schema::dropIfExists('risk_threshold_settings');
        Schema::dropIfExists('messaging_privacy_settings');
        Schema::dropIfExists('wallet_transaction_policies');
        Schema::dropIfExists('wallet_settings');
        Schema::dropIfExists('user_security_policies');
        Schema::dropIfExists('user_settings');
    }
};
