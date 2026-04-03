<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supported_networks', function (Blueprint $table) {
            $table->id();
            $table->string('chain', 16);
            $table->string('slug', 48)->unique();
            $table->string('display_name', 128);
            $table->unsignedBigInteger('chain_id')->nullable();
            $table->string('hrp', 16)->nullable();
            $table->boolean('is_testnet')->default(false);
            $table->string('explorer_tx_url_template', 512)->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['chain', 'is_testnet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supported_networks');
    }
};
