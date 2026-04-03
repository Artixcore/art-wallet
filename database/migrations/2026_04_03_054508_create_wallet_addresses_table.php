<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('chain', 32);
            $table->string('address', 128);
            $table->string('derivation_path', 128)->nullable();
            $table->unsignedInteger('derivation_index')->default(0);
            $table->boolean('is_change')->default(false);
            $table->timestamps();

            $table->index(['wallet_id', 'chain']);
            $table->unique(['wallet_id', 'chain', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_addresses');
    }
};
