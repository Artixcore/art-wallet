<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verified_wallet_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chain', 16);
            $table->string('address', 128);
            $table->timestamp('verified_at')->useCurrent();
            $table->string('verification_source', 32)->default('wallet_sync');
            $table->timestamps();

            $table->unique(['chain', 'address']);
            $table->index(['user_id', 'chain']);
        });

        Schema::create('conversation_direct_index', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_low_id');
            $table->unsignedBigInteger('user_high_id');
            $table->timestamps();

            $table->foreign('user_low_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_high_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_low_id', 'user_high_id']);
            $table->unique('conversation_id');
        });

        Schema::create('messaging_contact_pairs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_low_id');
            $table->unsignedBigInteger('user_high_id');
            $table->timestamps();

            $table->foreign('user_low_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_high_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_low_id', 'user_high_id']);
        });

        Schema::create('address_resolution_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('searcher_id')->constrained('users')->cascadeOnDelete();
            $table->char('address_hash', 64);
            $table->string('outcome', 32);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['searcher_id', 'created_at']);
            $table->index('address_hash');
        });

        Schema::table('messaging_privacy_settings', function (Blueprint $table) {
            $table->string('discoverable_by_sol_address', 32)->default('off')->after('safety_warnings_enabled');
            $table->boolean('require_dm_approval')->default(false)->after('discoverable_by_sol_address');
            $table->boolean('hide_profile_until_dm_accepted')->default(true)->after('require_dm_approval');
        });

        $this->backfillDirectIndexAndContacts();
    }

    public function down(): void
    {
        Schema::table('messaging_privacy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'discoverable_by_sol_address',
                'require_dm_approval',
                'hide_profile_until_dm_accepted',
            ]);
        });

        Schema::dropIfExists('address_resolution_audit');
        Schema::dropIfExists('messaging_contact_pairs');
        Schema::dropIfExists('conversation_direct_index');
        Schema::dropIfExists('verified_wallet_addresses');
    }

    private function backfillDirectIndexAndContacts(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        $convIds = DB::table('conversations')
            ->where('type', 'direct')
            ->orderBy('id')
            ->pluck('id');

        $seenPairs = [];

        foreach ($convIds as $convId) {
            $userIds = DB::table('conversation_members')
                ->where('conversation_id', $convId)
                ->orderBy('user_id')
                ->pluck('user_id')
                ->all();

            if (count($userIds) !== 2) {
                continue;
            }

            $low = min((int) $userIds[0], (int) $userIds[1]);
            $high = max((int) $userIds[0], (int) $userIds[1]);
            $pairKey = $low.':'.$high;

            if (isset($seenPairs[$pairKey])) {
                continue;
            }
            $seenPairs[$pairKey] = true;

            DB::table('conversation_direct_index')->insert([
                'conversation_id' => $convId,
                'user_low_id' => $low,
                'user_high_id' => $high,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('messaging_contact_pairs')->insertOrIgnore([
                'user_low_id' => $low,
                'user_high_id' => $high,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
