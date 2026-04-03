<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16);
            $table->string('network', 32);
            $table->unsignedTinyInteger('decimals');
            $table->string('contract_address', 128)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['code', 'network']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
