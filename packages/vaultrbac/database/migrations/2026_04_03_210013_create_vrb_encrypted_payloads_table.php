<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $name = VaultrbacTables::name('encrypted_payloads');

        Schema::create($name, function (Blueprint $table) {
            $table->id();
            $table->text('ciphertext');
            $table->text('dek_wrapped')->nullable();
            $table->string('key_version', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('encrypted_payloads'));
    }
};
