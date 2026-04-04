<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('subsystem', 64);
            $table->string('check_key', 128);
            $table->string('status', 32);
            $table->timestamp('observed_at');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->json('detail_json')->nullable();
            $table->string('probe_version', 32)->default('1');
            $table->timestamps();

            $table->unique(['subsystem', 'check_key']);
            $table->index('observed_at');
        });

        Schema::create('subsystem_status_history', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at');
            $table->string('subsystem', 64);
            $table->json('snapshot_json');
            $table->timestamps();

            $table->index(['subsystem', 'recorded_at']);
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 32);
            $table->string('event_type', 128);
            $table->foreignId('subject_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resource_type', 64)->nullable();
            $table->string('resource_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('sensitivity', 16)->default('low');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index('occurred_at');
        });

        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('severity', 32);
            $table->string('state', 32);
            $table->string('summary', 512);
            $table->json('correlation_keys')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'severity']);
        });

        Schema::create('anomaly_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 64);
            $table->unsignedSmallInteger('score')->default(0);
            $table->json('metadata_json')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['type', 'occurred_at']);
            $table->index('occurred_at');
        });

        Schema::create('job_failure_events', function (Blueprint $table) {
            $table->id();
            $table->string('connection', 64)->nullable();
            $table->string('queue', 64)->nullable();
            $table->string('payload_class', 255)->nullable();
            $table->string('exception_class', 255)->nullable();
            $table->timestamp('failed_at');
            $table->timestamps();

            $table->index('failed_at');
        });

        Schema::create('rpc_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('chain', 32);
            $table->string('provider', 64)->nullable();
            $table->string('status', 32);
            $table->timestamp('observed_at');
            $table->unsignedBigInteger('block_height')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->json('detail_json')->nullable();
            $table->string('probe_version', 32)->default('1');
            $table->timestamps();

            $table->unique(['chain', 'provider']);
            $table->index('observed_at');
        });

        Schema::create('notification_delivery_failures', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32);
            $table->string('error_class', 128)->nullable();
            $table->unsignedInteger('count_in_window')->default(1);
            $table->timestamp('window_started_at');
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['channel', 'window_started_at']);
        });

        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('severity', 32);
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->string('status', 32);
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
        });

        Schema::create('operator_alert_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_alert_id')->constrained('system_alerts')->cascadeOnDelete();
            $table->foreignId('operator_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('ack_at');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['system_alert_id', 'operator_user_id'], 'op_alert_ack_alert_user_uq');
        });

        Schema::create('dashboard_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route', 255);
            $table->string('ip_address', 45)->nullable();
            $table->json('panels')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('remediation_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_type', 64);
            $table->string('payload_hash', 64);
            $table->uuid('idempotency_key')->unique();
            $table->string('status', 32);
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['action_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remediation_actions');
        Schema::dropIfExists('dashboard_access_logs');
        Schema::dropIfExists('operator_alert_acknowledgements');
        Schema::dropIfExists('system_alerts');
        Schema::dropIfExists('notification_delivery_failures');
        Schema::dropIfExists('rpc_health_checks');
        Schema::dropIfExists('job_failure_events');
        Schema::dropIfExists('anomaly_events');
        Schema::dropIfExists('security_incidents');
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('subsystem_status_history');
        Schema::dropIfExists('system_health_checks');
    }
};
