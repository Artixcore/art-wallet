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
        $name = VaultrbacTables::name('super_user_actions');

        Schema::create($name, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->index();
            $table->string('action', 191);
            $table->text('justification')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('request_id', 128)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(VaultrbacTables::name('super_user_actions'));
    }
};
