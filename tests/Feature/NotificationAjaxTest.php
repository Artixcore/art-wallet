<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Notifications\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NotificationAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_dropdown_returns_envelope_and_unread_count(): void
    {
        $user = User::factory()->create();

        InAppNotification::query()->create([
            'user_id' => $user->id,
            'category' => NotificationCategory::Transaction,
            'severity' => NotificationSeverity::Info,
            'title_key' => 'tx.broadcast_success',
            'body_params' => ['txid' => 'abc'],
            'requires_ack' => false,
            'blocking' => false,
        ]);

        $this->actingAs($user)->getJson('/ajax/notifications/dropdown?limit=5')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('data.notifications.0.title_key', 'tx.broadcast_success');
    }

    public function test_mark_all_read_clears_unread(): void
    {
        $user = User::factory()->create();

        InAppNotification::query()->create([
            'user_id' => $user->id,
            'category' => NotificationCategory::System,
            'severity' => NotificationSeverity::Info,
            'title_key' => 'security.generic',
            'body_params' => null,
            'requires_ack' => false,
            'blocking' => false,
        ]);

        $this->actingAs($user)->postJson('/ajax/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.unread_count', 0);
    }

    public function test_update_preferences_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/ajax/notifications/preferences', [
            'preferences' => [
                [
                    'category' => 'not_a_real_category',
                    'toast_enabled' => true,
                    'persist_enabled' => true,
                    'email_enabled' => false,
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    public function test_update_preferences_persists(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/ajax/notifications/preferences', [
            'preferences' => [
                [
                    'category' => 'transaction',
                    'toast_enabled' => false,
                    'persist_enabled' => true,
                    'email_enabled' => false,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)->getJson('/ajax/notifications/preferences')
            ->assertOk()
            ->assertJsonPath('data.preferences.0.category', 'transaction')
            ->assertJsonPath('data.preferences.0.toast_enabled', false);
    }
}
