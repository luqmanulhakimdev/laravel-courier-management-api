<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CourierResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_resource_transforms_model_to_array(): void
    {
        $registeredAt = Carbon::create(2026, 2, 1, 10, 0, 0);
        $courier = Courier::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081299998888',
            'email' => 'budi.santoso@example.com',
            'address' => 'Jl. Sudirman No. 1',
            'level' => 4,
            'registered_at' => $registeredAt,
        ]);

        $resource = new CourierResource($courier);
        $result = $resource->toArray(Request::create('/api/couriers/'.$courier->id));

        $this->assertSame($courier->id, $result['id']);
        $this->assertSame('Budi Santoso', $result['name']);
        $this->assertSame('081299998888', $result['phone']);
        $this->assertSame('budi.santoso@example.com', $result['email']);
        $this->assertSame('Jl. Sudirman No. 1', $result['address']);
        $this->assertSame(4, $result['level']);
        $this->assertSame($registeredAt->toISOString(), $result['registered_at']);
        $this->assertSame($courier->created_at->toISOString(), $result['created_at']);
        $this->assertSame($courier->updated_at->toISOString(), $result['updated_at']);
    }

    public function test_courier_resource_handles_null_registered_at(): void
    {
        $courier = Courier::factory()->create([
            'registered_at' => null,
        ]);

        $resource = new CourierResource($courier);
        $result = $resource->toArray(Request::create('/api/couriers/'.$courier->id));

        $this->assertNull($result['registered_at']);
    }
}
