<?php

namespace Tests\Unit\Models;

use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CourierModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_has_expected_fillable_attributes(): void
    {
        $courier = new Courier;
        $this->assertEquals(
            ['name', 'phone', 'email', 'address', 'level', 'registered_at'],
            $courier->getFillable()
        );
    }

    public function test_courier_casts_attributes_correctly(): void
    {
        $courier = Courier::factory()->create([
            'level' => '3',
            'registered_at' => '2026-05-10 08:30:00',
        ]);

        $this->assertIsInt($courier->level);
        $this->assertSame(3, $courier->level);
        $this->assertInstanceOf(Carbon::class, $courier->registered_at);
        $this->assertSame('2026-05-10 08:30:00', $courier->registered_at->format('Y-m-d H:i:s'));
    }

    public function test_courier_handles_null_registered_at(): void
    {
        $courier = Courier::factory()->create([
            'registered_at' => null,
        ]);

        $this->assertNull($courier->registered_at);
    }
}
