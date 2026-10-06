<?php

namespace Tests\Feature\Migrated;

use App\Models\MeliNotification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    public function test_lists_only_unread_notifications_of_account(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        MeliNotification::factory()->count(2)->create(['mercadolibre_account_id' => $account->id]);
        MeliNotification::factory()->create(['mercadolibre_account_id' => $account->id, 'read_at' => now()]);
        MeliNotification::factory()->create();

        $this->getJson('/api/notifications', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_marks_notification_as_read(): void
    {
        [$operator, $account] = $this->operatorWithAccount();
        $notification = MeliNotification::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->patchJson("/api/notifications/{$notification->id}/read", [], $this->authHeaders($operator))->assertOk();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_cannot_mark_notification_of_other_account(): void
    {
        [$admin] = $this->adminWithAccount();
        $foreign = MeliNotification::factory()->create();

        $this->patchJson("/api/notifications/{$foreign->id}/read", [], $this->authHeaders($admin))->assertNotFound();
        $this->assertNull($foreign->fresh()->read_at);
    }
}
