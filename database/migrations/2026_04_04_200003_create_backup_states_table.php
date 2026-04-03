<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->timestamp('mnemonic_verified_at')->nullable();
            $table->timestamp('recovery_kit_created_at')->nullable();
            $table->timestamp('server_backup_uploaded_at')->nullable();
            $table->string('hint_public', 255)->nullable();
            $table->boolean('strict_security_mode')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_states');
    }
};
