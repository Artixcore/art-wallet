<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('in_app_notification_id')->constrained('in_app_notifications')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'in_app_notification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_acknowledgements');
    }
};
