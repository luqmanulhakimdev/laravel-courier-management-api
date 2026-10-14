<?php

namespace Tests\Feature;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => 'Budi Agung', 'phone' => '081234567890', 'email' => 'budi@example.com', 'address' => 'Jakarta', 'level' => 2], $overrides);
    }

    public function test_store_validates_and_saves_courier_with_default_registration_time(): void
    {
        $this->postJson('/api/couriers', $this->payload())->assertCreated()->assertJsonPath('data.name', 'Budi Agung');
        $this->assertDatabaseHas('couriers', ['email' => 'budi@example.com', 'level' => 2]);
        $this->assertNotNull(Courier::first()->registered_at);
    }

    public function test_store_rejects_invalid_fields_duplicates_and_extra_attributes(): void
    {
        $this->postJson('/api/couriers', ['name' => 'Missing'])->assertUnprocessable();
        $this->postJson('/api/couriers', $this->payload(['email' => 'bad', 'level' => 2]))->assertUnprocessable();
        Courier::factory()->create(['email' => 'taken@example.com']);
        $this->postJson('/api/couriers', $this->payload(['email' => 'taken@example.com']))->assertUnprocessable();
        foreach ([0, 6, 'two'] as $level) {
            $this->postJson('/api/couriers', $this->payload(['email' => null, 'level' => $level]))->assertUnprocessable();
        }
        $this->postJson('/api/couriers', $this->payload(['id' => 99]))->assertUnprocessable();
    }

    public function test_index_searches_all_terms_case_insensitively_and_filters_levels(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung', 'level' => 2]);
        Courier::factory()->create(['name' => 'Agung Budi Santoso', 'level' => 3]);
        Courier::factory()->create(['name' => 'Budi only', 'level' => 4]);
        $this->getJson('/api/couriers?search=BUDI+AGUNG&level=2,3&per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/couriers?search=missing+name')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/couriers?level=2,6')->assertUnprocessable();
        $this->getJson('/api/couriers?level=')->assertUnprocessable();
    }

    public function test_index_sorting_pagination_and_parameter_validation(): void
    {
        Courier::factory()->create(['name' => 'Zed', 'registered_at' => '2025-01-01']);
        Courier::factory()->create(['name' => 'Alice', 'registered_at' => '2024-01-01']);
        $this->getJson('/api/couriers')->assertOk()->assertJsonPath('data.0.name', 'Alice');
        $this->getJson('/api/couriers?sort_by=registered_at')->assertJsonPath('data.0.name', 'Zed');
        $this->getJson('/api/couriers?sort_by=email')->assertUnprocessable();
        $this->getJson('/api/couriers?per_page=101')->assertUnprocessable();
        $this->getJson('/api/couriers?per_page=1')->assertJsonPath('meta.last_page', 2)->assertJsonPath('meta.per_page', 1);
    }

    public function test_show_update_partial_update_and_delete(): void
    {
        $courier = Courier::factory()->create(['email' => 'same@example.com']);
        $this->getJson('/api/couriers/'.$courier->id)->assertOk()->assertJsonStructure(['data' => ['id', 'name', 'phone', 'email', 'address', 'level', 'registered_at', 'created_at', 'updated_at']]);
        $this->patchJson('/api/couriers/'.$courier->id, ['email' => 'same@example.com', 'name' => 'Changed'])->assertOk()->assertJsonPath('data.name', 'Changed');
        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'name' => 'Changed', 'email' => 'same@example.com']);
        $this->patchJson('/api/couriers/'.$courier->id, ['id' => 999])->assertUnprocessable();
        $this->patchJson('/api/couriers/'.$courier->id, ['level' => 7])->assertUnprocessable();
        $this->deleteJson('/api/couriers/'.$courier->id)->assertNoContent();
        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
    }

    public function test_missing_courier_returns_not_found_and_put_requires_all_fields(): void
    {
        $this->getJson('/api/couriers/99999')->assertNotFound();
        $this->patchJson('/api/couriers/99999', [])->assertNotFound();
        $courier = Courier::factory()->create();
        $this->putJson('/api/couriers/'.$courier->id, ['name' => 'Only name'])->assertUnprocessable();
        $this->deleteJson('/api/couriers/99999')->assertNotFound();
    }

    public function test_update_rejects_an_email_owned_by_another_courier_and_preserves_omitted_fields(): void
    {
        $owner = Courier::factory()->create(['email' => 'owner@example.com']);
        $courier = Courier::factory()->create(['email' => 'current@example.com', 'address' => 'Bandung']);

        $this->patchJson('/api/couriers/'.$courier->id, ['email' => $owner->email])->assertUnprocessable();
        $this->patchJson('/api/couriers/'.$courier->id, ['name' => 'Updated name'])->assertOk();
        $this->assertDatabaseHas('couriers', ['id' => $courier->id, 'name' => 'Updated name', 'address' => 'Bandung']);
    }

    public function test_index_combines_search_level_sorting_and_pagination(): void
    {
        Courier::factory()->create(['name' => 'Budiono Hadi Agung', 'level' => 2, 'registered_at' => '2025-01-01']);
        Courier::factory()->create(['name' => 'Agung Budi Santoso', 'level' => 3, 'registered_at' => '2026-01-01']);
        Courier::factory()->create(['name' => 'Budi Agung', 'level' => 4, 'registered_at' => '2027-01-01']);

        $this->getJson('/api/couriers?search=budi+agung&level=2,3&sort_by=registered_at&per_page=1&page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Agung Budi Santoso')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);
    }
}
