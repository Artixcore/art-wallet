<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_ENCRYPTED = 'vrb_encrypted_payloads';

    private const LEGACY_AUDIT = 'vrb_audit_events';

    public function up(): void
    {
        $meta = VaultrbacTables::name('encrypted_metadata');
        if (Schema::hasTable(self::LEGACY_ENCRYPTED) && ! Schema::hasTable($meta)) {
            Schema::rename(self::LEGACY_ENCRYPTED, $meta);
        }

        if (Schema::hasTable($meta)) {
            Schema::table($meta, function (Blueprint $table) use ($meta): void {
                if (Schema::hasColumn($meta, 'dek_wrapped') && ! Schema::hasColumn($meta, 'wrapped_dek')) {
                    $table->renameColumn('dek_wrapped', 'wrapped_dek');
                }
                if (! Schema::hasColumn($meta, 'nonce')) {
                    $table->binary('nonce', 32)->nullable()->after('key_version');
                }
                if (! Schema::hasColumn($meta, 'tag')) {
                    $table->binary('tag', 16)->nullable();
                }
                if (! Schema::hasColumn($meta, 'algo')) {
                    $table->string('algo', 32)->nullable();
                }
                if (! Schema::hasColumn($meta, 'plaintext_fingerprint')) {
                    $table->binary('plaintext_fingerprint', 32)->nullable();
                }
            });
        }

        $audit = VaultrbacTables::name('audit_logs');
        if (Schema::hasTable(self::LEGACY_AUDIT) && ! Schema::hasTable($audit)) {
            Schema::rename(self::LEGACY_AUDIT, $audit);
        }
    }

    public function down(): void
    {
        $meta = VaultrbacTables::name('encrypted_metadata');
        if (Schema::hasTable($meta) && ! Schema::hasTable(self::LEGACY_ENCRYPTED)) {
            Schema::rename($meta, self::LEGACY_ENCRYPTED);
        }

        if (Schema::hasTable(self::LEGACY_ENCRYPTED)) {
            Schema::table(self::LEGACY_ENCRYPTED, function (Blueprint $table): void {
                if (Schema::hasColumn(self::LEGACY_ENCRYPTED, 'wrapped_dek') && ! Schema::hasColumn(self::LEGACY_ENCRYPTED, 'dek_wrapped')) {
                    $table->renameColumn('wrapped_dek', 'dek_wrapped');
                }
                foreach (['nonce', 'tag', 'algo', 'plaintext_fingerprint'] as $col) {
                    if (Schema::hasColumn(self::LEGACY_ENCRYPTED, $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        $audit = VaultrbacTables::name('audit_logs');
        if (Schema::hasTable($audit) && ! Schema::hasTable(self::LEGACY_AUDIT)) {
            Schema::rename($audit, self::LEGACY_AUDIT);
        }
    }
};
