<?php

namespace Tests\Feature\Migrated;

use App\Models\BulkPublishCode;
use Tests\TestCase;

class BulkPublishControllerTest extends TestCase
{
    public function test_store_registers_unique_codes_from_text(): void
    {
        [$admin, $account] = $this->adminWithAccount();

        $this->postJson('/api/bulk-publish/codes', ['codes' => "B01\nB02, B03\nB01"], $this->authHeaders($admin))
            ->assertCreated()
            ->assertJsonPath('data.registered', 3);

        $this->assertSame(3, BulkPublishCode::where('mercadolibre_account_id', $account->id)->where('status', 'pending')->count());
    }

    public function test_store_accepts_array(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->postJson('/api/bulk-publish/codes', ['skus' => ['B0A', 'B0B']], $this->authHeaders($admin))
            ->assertCreated()
            ->assertJsonPath('data.registered', 2);
    }

    public function test_store_requires_codes(): void
    {
        [$admin] = $this->adminWithAccount();

        $this->postJson('/api/bulk-publish/codes', [], $this->authHeaders($admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['skus']);
    }

    public function test_index_lists_pending_or_by_date(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        BulkPublishCode::factory()->create(['mercadolibre_account_id' => $account->id]);
        BulkPublishCode::factory()->create(['mercadolibre_account_id' => $account->id, 'status' => 'published']);
        BulkPublishCode::factory()->create(['mercadolibre_account_id' => $account->id, 'created_at' => '2026-01-10 10:00:00']);

        $this->getJson('/api/bulk-publish/codes', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/bulk-publish/codes?date=2026-01-10', $this->authHeaders($admin))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_is_forbidden_for_operator(): void
    {
        [$operator] = $this->operatorWithAccount();

        $this->getJson('/api/bulk-publish/codes', $this->authHeaders($operator))->assertForbidden();
    }
}
