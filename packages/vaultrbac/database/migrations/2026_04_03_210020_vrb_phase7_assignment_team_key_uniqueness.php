<?php

declare(strict_types=1);

use Artwallet\VaultRbac\Database\VaultrbacTables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $modelRoles = VaultrbacTables::name('model_roles');
        Schema::table($modelRoles, function (Blueprint $table) use ($modelRoles): void {
            if (! Schema::hasColumn($modelRoles, 'team_key')) {
                $table->unsignedBigInteger('team_key')->default(0);
            }
        });
        DB::table($modelRoles)->update([
            'team_key' => DB::raw('COALESCE(team_id, 0)'),
        ]);
        if (! Schema::hasIndex($modelRoles, 'vrb_model_roles_assignment_uq')) {
            Schema::table($modelRoles, function (Blueprint $table): void {
                $table->unique(
                    ['tenant_id', 'team_key', 'role_id', 'model_type', 'model_id'],
                    'vrb_model_roles_assignment_uq',
                );
            });
        }

        $modelPerms = VaultrbacTables::name('model_permissions');
        Schema::table($modelPerms, function (Blueprint $table) use ($modelPerms): void {
            if (! Schema::hasColumn($modelPerms, 'team_key')) {
                $table->unsignedBigInteger('team_key')->default(0);
            }
        });
        DB::table($modelPerms)->update([
            'team_key' => DB::raw('COALESCE(team_id, 0)'),
        ]);
        if (! Schema::hasIndex($modelPerms, 'vrb_model_permissions_assignment_uq')) {
            Schema::table($modelPerms, function (Blueprint $table): void {
                $table->unique(
                    ['tenant_id', 'team_key', 'permission_id', 'model_type', 'model_id', 'effect'],
                    'vrb_model_permissions_assignment_uq',
                );
            });
        }
    }

    public function down(): void
    {
        $modelRoles = VaultrbacTables::name('model_roles');
        if (Schema::hasIndex($modelRoles, 'vrb_model_roles_assignment_uq')) {
            Schema::table($modelRoles, function (Blueprint $table): void {
                $table->dropUnique('vrb_model_roles_assignment_uq');
            });
        }
        Schema::table($modelRoles, function (Blueprint $table) use ($modelRoles): void {
            if (Schema::hasColumn($modelRoles, 'team_key')) {
                $table->dropColumn('team_key');
            }
        });

        $modelPerms = VaultrbacTables::name('model_permissions');
        if (Schema::hasIndex($modelPerms, 'vrb_model_permissions_assignment_uq')) {
            Schema::table($modelPerms, function (Blueprint $table): void {
                $table->dropUnique('vrb_model_permissions_assignment_uq');
            });
        }
        Schema::table($modelPerms, function (Blueprint $table) use ($modelPerms): void {
            if (Schema::hasColumn($modelPerms, 'team_key')) {
                $table->dropColumn('team_key');
            }
        });
    }
};
