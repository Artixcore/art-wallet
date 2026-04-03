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
        $encrypted = VaultrbacTables::name('encrypted_metadata');

        foreach ([VaultrbacTables::name('permissions'), VaultrbacTables::name('roles')] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($encrypted, $tableName): void {
                if (! Schema::hasColumn($tableName, 'metadata_json_id')) {
                    $table->foreignId('metadata_json_id')->nullable()->after('metadata')->constrained($encrypted)->nullOnDelete();
                }
            });
        }

        $modelRoles = VaultrbacTables::name('model_roles');
        Schema::table($modelRoles, function (Blueprint $table) use ($encrypted, $modelRoles): void {
            if (! Schema::hasColumn($modelRoles, 'metadata_json_id')) {
                $table->foreignId('metadata_json_id')->nullable()->after('metadata')->constrained($encrypted)->nullOnDelete();
            }
        });

        $modelPerms = VaultrbacTables::name('model_permissions');
        Schema::table($modelPerms, function (Blueprint $table) use ($encrypted, $modelPerms): void {
            if (! Schema::hasColumn($modelPerms, 'metadata_json_id')) {
                $table->foreignId('metadata_json_id')->nullable()->after('metadata')->constrained($encrypted)->nullOnDelete();
            }
        });

        $scopes = VaultrbacTables::name('permission_scopes');
        Schema::table($scopes, function (Blueprint $table) use ($encrypted, $scopes): void {
            if (! Schema::hasColumn($scopes, 'metadata_json_id')) {
                $table->foreignId('metadata_json_id')->nullable()->after('metadata')->constrained($encrypted)->nullOnDelete();
            }
        });

        $conditions = VaultrbacTables::name('permission_conditions');
        Schema::table($conditions, function (Blueprint $table) use ($encrypted, $conditions): void {
            if (! Schema::hasColumn($conditions, 'expression_json_id')) {
                $table->foreignId('expression_json_id')->nullable()->after('expression')->constrained($encrypted)->nullOnDelete();
            }
        });

        $approvals = VaultrbacTables::name('approval_requests');
        Schema::table($approvals, function (Blueprint $table) use ($encrypted, $approvals): void {
            if (! Schema::hasColumn($approvals, 'payload_json_id')) {
                $table->foreignId('payload_json_id')->nullable()->after('payload')->constrained($encrypted)->nullOnDelete();
            }
            if (! Schema::hasColumn($approvals, 'required_approvers_json_id')) {
                $table->foreignId('required_approvers_json_id')->nullable()->after('required_approvers')->constrained($encrypted)->nullOnDelete();
            }
        });

        $audit = VaultrbacTables::name('audit_logs');
        Schema::table($audit, function (Blueprint $table) use ($encrypted, $audit): void {
            if (! Schema::hasColumn($audit, 'diff_json_id')) {
                $table->foreignId('diff_json_id')->nullable()->after('diff')->constrained($encrypted)->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $audit = VaultrbacTables::name('audit_logs');
        Schema::table($audit, function (Blueprint $table) use ($audit): void {
            if (Schema::hasColumn($audit, 'diff_json_id')) {
                $table->dropConstrainedForeignId('diff_json_id');
            }
        });

        $approvals = VaultrbacTables::name('approval_requests');
        Schema::table($approvals, function (Blueprint $table) use ($approvals): void {
            if (Schema::hasColumn($approvals, 'required_approvers_json_id')) {
                $table->dropConstrainedForeignId('required_approvers_json_id');
            }
            if (Schema::hasColumn($approvals, 'payload_json_id')) {
                $table->dropConstrainedForeignId('payload_json_id');
            }
        });

        $conditions = VaultrbacTables::name('permission_conditions');
        Schema::table($conditions, function (Blueprint $table) use ($conditions): void {
            if (Schema::hasColumn($conditions, 'expression_json_id')) {
                $table->dropConstrainedForeignId('expression_json_id');
            }
        });

        foreach ([VaultrbacTables::name('permission_scopes'), VaultrbacTables::name('model_permissions'), VaultrbacTables::name('model_roles')] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'metadata_json_id')) {
                    $table->dropConstrainedForeignId('metadata_json_id');
                }
            });
        }

        foreach ([VaultrbacTables::name('permissions'), VaultrbacTables::name('roles')] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'metadata_json_id')) {
                    $table->dropConstrainedForeignId('metadata_json_id');
                }
            });
        }
    }
};
