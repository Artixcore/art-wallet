<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->default('default');
            $table->string('status')->default('draft');
            $table->string('execution_mode')->default('manual');
            $table->string('memory_mode')->default('off');
            $table->json('budget_json')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('agent_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->longText('system_prompt')->nullable();
            $table->longText('developer_prompt')->nullable();
            $table->json('variables_json')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'version']);
        });

        Schema::create('agent_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 64);
            $table->string('label')->nullable();
            $table->text('encrypted_payload');
            $table->string('key_fingerprint', 128)->nullable();
            $table->string('last4', 8)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider']);
        });

        Schema::create('agent_provider_bindings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('credential_id')->constrained('agent_api_credentials')->cascadeOnDelete();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('enabled')->default(true);
            $table->json('model_preferences_json')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'credential_id']);
        });

        Schema::create('agent_tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('tool_key', 128);
            $table->boolean('enabled')->default(false);
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'tool_key']);
        });

        Schema::create('agent_safety_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->json('permissions_json');
            $table->timestamps();

            $table->unique('agent_id');
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('name');
            $table->json('definition_json');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('trigger_type')->default('manual');
            $table->uuid('correlation_id');
            $table->json('budget_consumed_json')->nullable();
            $table->string('approval_token', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index('correlation_id');
        });

        Schema::create('workflow_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained('workflow_runs')->cascadeOnDelete();
            $table->string('node_id', 128);
            $table->string('status')->default('pending');
            $table->string('input_ref')->nullable();
            $table->string('output_ref')->nullable();
            $table->json('error_json')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->index(['workflow_run_id', 'sequence']);
        });

        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->unsignedBigInteger('credential_id')->nullable();
            $table->string('mode', 32)->default('chat');
            $table->string('status')->default('queued');
            $table->string('outcome')->nullable();
            $table->uuid('correlation_id');
            $table->string('provider', 64)->nullable();
            $table->string('model', 128)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('cost_estimate_json')->nullable();
            $table->json('usage_json')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->string('input_summary')->nullable();
            $table->text('output_text')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'agent_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('correlation_id');
        });

        Schema::table('agent_runs', function (Blueprint $table) {
            $table->foreign('credential_id')->references('id')->on('agent_api_credentials')->nullOnDelete();
        });

        Schema::create('agent_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->text('encrypted_payload');
            $table->string('context_key', 128)->default('default');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'context_key']);
        });

        Schema::create('agent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('agent_run_id')->nullable()->constrained('agent_runs')->nullOnDelete();
            $table->string('level', 16)->default('info');
            $table->string('event', 128);
            $table->string('message');
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['agent_id', 'created_at']);
        });

        Schema::create('provider_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 64);
            $table->string('model', 128)->nullable();
            $table->json('metrics_json');
            $table->unsignedInteger('sample_count')->default(0);
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider', 'model']);
        });

        Schema::create('provider_health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained('agent_api_credentials')->cascadeOnDelete();
            $table->string('status', 32)->default('unknown');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->json('error_json')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['credential_id', 'checked_at']);
        });

        Schema::create('provider_comparison_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->uuid('correlation_id');
            $table->json('candidates_json');
            $table->json('scores_json');
            $table->string('winner_provider', 64)->nullable();
            $table->string('winner_model', 128)->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('agent_run_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_run_id')->constrained('agent_runs')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('tags_json')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'agent_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_run_feedback');
        Schema::dropIfExists('provider_comparison_results');
        Schema::dropIfExists('provider_health_checks');
        Schema::dropIfExists('provider_benchmarks');
        Schema::dropIfExists('agent_logs');
        Schema::dropIfExists('agent_memories');
        Schema::dropIfExists('workflow_run_steps');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('agent_safety_policies');
        Schema::dropIfExists('agent_tools');
        Schema::dropIfExists('agent_provider_bindings');
        Schema::dropIfExists('agent_runs');
        Schema::dropIfExists('agent_api_credentials');
        Schema::dropIfExists('agent_prompt_versions');
        Schema::dropIfExists('agents');
    }
};
