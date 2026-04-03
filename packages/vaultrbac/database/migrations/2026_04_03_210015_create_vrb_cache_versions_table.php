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
        $name = VaultrbacTables::name('cache_versions');

        Schema::create($name, function (Blueprint $table) {
            $table->string('cache_key', 191)->primary();
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('cache_versions'));
    }
};
