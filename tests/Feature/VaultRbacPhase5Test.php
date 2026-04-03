<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Artwallet\VaultRbac\Contracts\ApprovalWorkflowInterface;
use Artwallet\VaultRbac\Database\VaultrbacTables;
use Artwallet\VaultRbac\Facades\VaultRbac;
use Artwallet\VaultRbac\Models\AuditEvent;
use Artwallet\VaultRbac\Models\ModelRole;
use Artwallet\VaultRbac\Models\Role;
use Artwallet\VaultRbac\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class VaultRbacPhase5Test extends TestCase
{
    use RefreshDatabase;

    public function test_audit_chain_links_consecutive_writes(): void
    {
        config([
            'vaultrbac.audit.enabled' => true,
            'vaultrbac.audit.register_listeners' => true,
        ]);

        $tenant = Tenant::query()->create([
            'slug' => 'audit',
            'name' => 'Audit',
            'status' => 'active',
        ]);

        Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'r1',
            'activation_state' => 'active',
        ]);

        Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'r2',
            'activation_state' => 'active',
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);
        config(['vaultrbac.default_tenant_id' => $tenant->id]);

        $user->assignRole('r1', $tenant->id);
        $user->assignRole('r2', $tenant->id);

        $events = AuditEvent::query()->orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertSame((string) config('vaultrbac.audit.genesis_prev_hash'), (string) $events[0]->prev_hash);
        $this->assertSame((string) $events[0]->row_hash, (string) $events[1]->prev_hash);
        $this->assertNotNull($events[0]->signature);
    }

    public function test_approval_workflow_assigns_role_on_approve(): void
    {
        $tenant = Tenant::query()->create([
            'slug' => 'appr',
            'name' => 'Appr',
            'status' => 'active',
        ]);

        Role::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'privileged',
            'activation_state' => 'active',
        ]);

        $subject = User::factory()->create();
        $requester = User::factory()->create();
        $approver = User::factory()->create();

        $workflow = app(ApprovalWorkflowInterface::class);
        $request = $workflow->requestRoleAssignment(
            $subject,
            'privileged',
            $tenant->id,
            $requester->id,
        );

        $this->assertSame('pending', $request->fresh()->status);

        $workflow->approve($request->id, $approver->id);

        $this->assertSame('approved', $request->fresh()->status);

        $this->assertTrue(
            ModelRole::query()
                ->where('tenant_id', $tenant->id)
                ->where('model_type', $subject->getMorphClass())
                ->where('model_id', $subject->getKey())
                ->exists(),
        );

        $this->actingAs($subject);
        config(['vaultrbac.default_tenant_id' => $tenant->id]);
        $this->assertFalse(VaultRbac::check('any.unassigned'));
    }

    public function test_role_metadata_is_encrypted_when_enabled(): void
    {
        config(['vaultrbac.encryption.metadata.enabled' => true]);

        $tenant = Tenant::query()->create([
            'slug' => 'enc',
            'name' => 'Enc',
            'status' => 'active',
        ]);

        $secret = 'vault-metadata-secret-'.bin2hex(random_bytes(8));

        $role = Role::query()->create([
            'activation_state' => 'active',
            'metadata' => ['secret' => $secret],
            'name' => 'enc-role',
            'tenant_id' => $tenant->id,
        ]);

        $raw = (string) DB::table(VaultrbacTables::name('roles'))->where('id', $role->id)->value('metadata');
        $this->assertStringNotContainsString($secret, $raw);

        $role->refresh();
        $this->assertSame(['secret' => $secret], $role->metadata);
    }
}
