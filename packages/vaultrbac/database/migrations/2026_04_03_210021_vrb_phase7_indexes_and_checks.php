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
        $pivot = VaultrbacTables::name('role_permission');
        if (! Schema::hasIndex($pivot, 'vrb_role_permission_expand_idx')) {
            Schema::table($pivot, function (Blueprint $table): void {
                $table->index(['tenant_id', 'role_id', 'permission_id'], 'vrb_role_permission_expand_idx');
            });
        }

        $hierarchy = VaultrbacTables::name('role_hierarchy');
        if (! Schema::hasIndex($hierarchy, 'vrb_role_hierarchy_tenant_child_idx')) {
            Schema::table($hierarchy, function (Blueprint $table): void {
                $table->index(['tenant_id', 'child_role_id'], 'vrb_role_hierarchy_tenant_child_idx');
            });
        }

        $conditions = VaultrbacTables::name('permission_conditions');
        if (! Schema::hasIndex($conditions, 'vrb_permission_conditions_version_uq')) {
            Schema::table($conditions, function (Blueprint $table): void {
                $table->unique(['tenant_id', 'name', 'version'], 'vrb_permission_conditions_version_uq');
            });
        }
    }

    public function down(): void
    {
        $hierarchy = VaultrbacTables::name('role_hierarchy');
        if (Schema::hasIndex($hierarchy, 'vrb_role_hierarchy_tenant_child_idx')) {
            Schema::table($hierarchy, function (Blueprint $table): void {
                $table->dropIndex('vrb_role_hierarchy_tenant_child_idx');
            });
        }

        $conditions = VaultrbacTables::name('permission_conditions');
        if (Schema::hasIndex($conditions, 'vrb_permission_conditions_version_uq')) {
            Schema::table($conditions, function (Blueprint $table): void {
                $table->dropUnique('vrb_permission_conditions_version_uq');
            });
        }

        $pivot = VaultrbacTables::name('role_permission');
        if (Schema::hasIndex($pivot, 'vrb_role_permission_expand_idx')) {
            Schema::table($pivot, function (Blueprint $table): void {
                $table->dropIndex('vrb_role_permission_expand_idx');
            });
        }
    }
};
