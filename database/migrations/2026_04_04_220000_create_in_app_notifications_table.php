<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('severity', 16);
            $table->string('title_key', 128);
            $table->json('body_params')->nullable();
            $table->string('action_url', 512)->nullable();
            $table->string('dedupe_key', 191)->nullable();
            $table->nullableMorphs('subject');
            $table->boolean('requires_ack')->default(false);
            $table->boolean('blocking')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'created_at']);
            $table->unique(['user_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
    }
};
