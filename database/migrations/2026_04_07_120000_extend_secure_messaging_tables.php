<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedSmallInteger('ck_version')->default(1)->after('type');
        });

        Schema::table('conversation_members', function (Blueprint $table) {
            $table->unsignedSmallInteger('wrapped_ck_version')->default(1)->after('wrapped_conv_key_ciphertext');
            $table->foreignId('last_read_message_id')->nullable()->after('role')->constrained('messages')->nullOnDelete();
            $table->timestamp('last_read_at')->nullable()->after('last_read_message_id');
            $table->boolean('muted')->default(false)->after('last_read_at');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('client_message_id')->nullable()->after('version');
            $table->string('idempotency_key', 64)->nullable()->after('client_message_id');
            $table->char('ciphertext_sha256', 64)->nullable()->after('ciphertext');
            $table->index(['conversation_id', 'sender_id', 'idempotency_key'], 'messages_conv_sender_idempotency');
        });

        Schema::create('message_delivery_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('state', 32);
            $table->timestamps();

            $table->unique(['message_id', 'recipient_user_id']);
            $table->index(['recipient_user_id', 'state']);
        });

        Schema::create('messaging_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('device_id');
            $table->string('device_ed25519_public_key_b64', 128)->nullable();
            $table->string('device_x25519_public_key_b64', 128)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
        });

        Schema::create('messaging_security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 64);
            $table->string('severity', 32);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });

        Schema::table('message_attachments', function (Blueprint $table) {
            $table->longText('enc_manifest')->nullable()->after('message_id');
            $table->char('ciphertext_sha256', 64)->nullable()->after('size_bytes');
            $table->string('upload_state', 32)->default('pending')->after('crypto_meta');
            $table->string('mime_hint', 128)->nullable()->after('content_type');
        });
    }

    public function down(): void
    {
        Schema::table('message_attachments', function (Blueprint $table) {
            $table->dropColumn(['enc_manifest', 'ciphertext_sha256', 'upload_state', 'mime_hint']);
        });

        Schema::dropIfExists('messaging_security_events');
        Schema::dropIfExists('messaging_devices');
        Schema::dropIfExists('message_delivery_states');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conv_sender_idempotency');
            $table->dropColumn(['client_message_id', 'idempotency_key', 'ciphertext_sha256']);
        });

        Schema::table('conversation_members', function (Blueprint $table) {
            $table->dropForeign(['last_read_message_id']);
            $table->dropColumn(['wrapped_ck_version', 'last_read_message_id', 'last_read_at', 'muted']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('ck_version');
        });
    }
};
