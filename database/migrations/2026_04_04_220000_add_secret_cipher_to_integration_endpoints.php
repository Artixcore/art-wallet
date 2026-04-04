<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_endpoints', function (Blueprint $table) {
            $table->text('secret_cipher')->nullable()->after('secret_hash');
        });
    }

    public function down(): void
    {
        Schema::table('integration_endpoints', function (Blueprint $table) {
            $table->dropColumn('secret_cipher');
        });
    }
};
