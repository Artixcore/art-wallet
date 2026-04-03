<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_session_records', function (Blueprint $table) {
            $table->text('session_id_encrypted')->nullable()->after('session_id_hash');
        });
    }

    public function down(): void
    {
        Schema::table('user_session_records', function (Blueprint $table) {
            $table->dropColumn('session_id_encrypted');
        });
    }
};
