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
        $name = VaultrbacTables::name('encrypted_metadata');

        Schema::create($name, function (Blueprint $table) {
            $table->id();
            $table->longText('ciphertext');
            $table->text('wrapped_dek')->nullable();
            $table->string('key_version', 64)->nullable();
            $table->binary('nonce', 32)->nullable();
            $table->binary('tag', 16)->nullable();
            $table->string('algo', 32)->nullable();
            $table->binary('plaintext_fingerprint', 32)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('encrypted_metadata'));
    }
};
