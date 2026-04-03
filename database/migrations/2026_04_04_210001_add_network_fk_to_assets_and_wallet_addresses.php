<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('supported_network_id')->nullable()->after('id')->constrained('supported_networks')->nullOnDelete();
            $table->string('asset_type', 16)->default('native')->after('network');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('enabled');
        });

        Schema::table('wallet_addresses', function (Blueprint $table) {
            $table->foreignId('supported_network_id')->nullable()->after('wallet_id')->constrained('supported_networks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supported_network_id');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supported_network_id');
            $table->dropColumn(['asset_type', 'sort_order']);
        });
    }
};
