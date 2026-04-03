<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignId('supported_network_id')->constrained('supported_networks')->restrictOnDelete();
            $table->string('direction', 8)->default('out');
            $table->string('from_address', 128);
            $table->string('to_address', 128);
            $table->decimal('amount_atomic', 65, 0);
            $table->text('memo')->nullable();
            $table->text('fee_quote_json')->nullable();
            $table->char('intent_hash', 64);
            $table->string('status', 32);
            $table->timestamp('expires_at');
            $table->string('idempotency_client_key', 128)->nullable()->unique();
            $table->json('construction_payload_json')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'status', 'created_at']);
            $table->index('intent_hash');
        });

        Schema::create('signing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_intent_id')->constrained('transaction_intents')->cascadeOnDelete();
            $table->string('server_nonce', 64);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->unique('server_nonce');
            $table->index(['transaction_intent_id', 'consumed_at']);
        });

        Schema::create('signed_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_intent_id')->constrained('transaction_intents')->cascadeOnDelete();
            $table->string('signed_tx_hash', 128);
            $table->longText('raw_signed_hex')->nullable();
            $table->string('algorithm', 32);
            $table->timestamps();

            $table->unique('transaction_intent_id');
            $table->index('signed_tx_hash');
        });

        Schema::create('broadcast_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_intent_id')->constrained('transaction_intents')->cascadeOnDelete();
            $table->string('idempotency_key', 128)->unique();
            $table->string('rpc_label', 32)->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->string('error_class', 128)->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index('transaction_intent_id');
        });

        Schema::create('blockchain_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('txid', 128);
            $table->foreignId('supported_network_id')->constrained('supported_networks')->restrictOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 8);
            $table->string('counterparty_address', 128)->nullable();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->decimal('amount_atomic', 65, 0)->nullable();
            $table->unsignedBigInteger('block_height')->nullable();
            $table->unsignedInteger('confirmations')->default(0);
            $table->json('raw_metadata_json')->nullable();
            $table->foreignId('transaction_intent_id')->nullable()->constrained('transaction_intents')->nullOnDelete();
            $table->string('status', 32);
            $table->timestamps();

            $table->unique(['supported_network_id', 'txid']);
            $table->index(['wallet_id', 'status', 'created_at']);
        });

        Schema::create('transaction_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blockchain_transaction_id')->constrained('blockchain_transactions')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('source', 32);
            $table->timestamp('observed_at');
            $table->timestamps();

            $table->index('blockchain_transaction_id');
        });

        Schema::create('fee_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supported_network_id')->constrained('supported_networks')->cascadeOnDelete();
            $table->string('tier', 16);
            $table->json('value_json');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['supported_network_id', 'tier', 'expires_at']);
            $table->index('expires_at');
        });

        Schema::create('address_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 128);
            $table->foreignId('supported_network_id')->constrained('supported_networks')->restrictOnDelete();
            $table->string('address', 128);
            $table->text('notes_ciphertext')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'supported_network_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_book_entries');
        Schema::dropIfExists('fee_estimates');
        Schema::dropIfExists('transaction_status_histories');
        Schema::dropIfExists('blockchain_transactions');
        Schema::dropIfExists('broadcast_attempts');
        Schema::dropIfExists('signed_transactions');
        Schema::dropIfExists('signing_requests');
        Schema::dropIfExists('transaction_intents');
    }
};
