<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\CourierController;
use App\Http\Requests\CourierIndexRequest;
use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Route;
use Tests\TestCase;

class CourierControllerTest extends TestCase
{
    use RefreshDatabase;

    private CourierController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new CourierController;
    }

    public function test_index_returns_paginated_couriers_with_search_and_level_filter(): void
    {
        Courier::factory()->create(['name' => 'Ahmad Dani', 'level' => 1]);
        Courier::factory()->create(['name' => 'Ahmad Subardjo', 'level' => 2]);
        Courier::factory()->create(['name' => 'Budi Santoso', 'level' => 1]);

        $request = CourierIndexRequest::create('/api/couriers', 'GET', [
            'search' => 'Ahmad Dani',
            'level' => '1',
            'per_page' => 5,
        ]);
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->index($request);

        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $response = $result->response()->getData(true);
        $this->assertCount(1, $response['data']);
        $this->assertSame('Ahmad Dani', $response['data'][0]['name']);
    }

    public function test_index_sorts_by_registered_at_desc(): void
    {
        $c1 = Courier::factory()->create(['name' => 'Courier A', 'registered_at' => '2025-01-01 00:00:00']);
        $c2 = Courier::factory()->create(['name' => 'Courier B', 'registered_at' => '2026-01-01 00:00:00']);

        $request = CourierIndexRequest::create('/api/couriers', 'GET', [
            'sort_by' => 'registered_at',
        ]);
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->index($request);
        $response = $result->response()->getData(true);

        $this->assertSame($c2->id, $response['data'][0]['id']);
        $this->assertSame($c1->id, $response['data'][1]['id']);
    }

    public function test_index_sorts_by_name_and_id_by_default(): void
    {
        $c1 = Courier::factory()->create(['name' => 'Budi Santoso']);
        $c2 = Courier::factory()->create(['name' => 'Agung Wicaksono']);

        $request = CourierIndexRequest::create('/api/couriers', 'GET');
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->index($request);
        $response = $result->response()->getData(true);

        $this->assertSame('Agung Wicaksono', $response['data'][0]['name']);
        $this->assertSame('Budi Santoso', $response['data'][1]['name']);
    }

    public function test_store_creates_courier_with_explicit_registered_at(): void
    {
        $payload = [
            'name' => 'Joko Widodo',
            'phone' => '081234567890',
            'email' => 'joko@example.com',
            'address' => 'Solo',
            'level' => 3,
            'registered_at' => '2026-03-01 10:00:00',
        ];

        $request = StoreCourierRequest::create('/api/couriers', 'POST', $payload);
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->store($request);

        $this->assertInstanceOf(CourierResource::class, $result);
        $this->assertDatabaseHas('couriers', [
            'name' => 'Joko Widodo',
            'email' => 'joko@example.com',
            'level' => 3,
        ]);
    }

    public function test_store_creates_courier_with_default_registered_at_when_not_provided(): void
    {
        $payload = [
            'name' => 'Mahendra',
            'phone' => '081234567891',
            'email' => 'mahendra@example.com',
            'level' => 2,
        ];

        $request = StoreCourierRequest::create('/api/couriers', 'POST', $payload);
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->store($request);

        $this->assertInstanceOf(CourierResource::class, $result);
        $created = Courier::where('email', 'mahendra@example.com')->first();
        $this->assertNotNull($created);
        $this->assertNotNull($created->registered_at);
    }

    public function test_show_returns_courier_resource(): void
    {
        $courier = Courier::factory()->create();

        $result = $this->controller->show($courier);

        $this->assertInstanceOf(CourierResource::class, $result);
        $this->assertSame($courier->id, $result->resource->id);
    }

    public function test_update_modifies_attributes_and_parses_registered_at_string(): void
    {
        $courier = Courier::factory()->create([
            'name' => 'Old Name',
            'registered_at' => '2025-01-01 00:00:00',
        ]);

        $request = UpdateCourierRequest::create('/api/couriers/'.$courier->id, 'PATCH', [
            'name' => 'New Name',
            'registered_at' => '2026-07-15 14:00:00',
        ]);
        $route = new Route('PATCH', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', $courier);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->update($request, $courier);

        $this->assertInstanceOf(CourierResource::class, $result);
        $this->assertSame('New Name', $result->resource->name);
        $this->assertSame('2026-07-15 14:00:00', $result->resource->registered_at->format('Y-m-d H:i:s'));
    }

    public function test_update_sets_registered_at_to_null_when_null_passed(): void
    {
        $courier = Courier::factory()->create([
            'registered_at' => '2025-01-01 00:00:00',
        ]);

        $request = UpdateCourierRequest::create('/api/couriers/'.$courier->id, 'PATCH', [
            'registered_at' => null,
        ]);
        $route = new Route('PATCH', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', $courier);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);
        $request->validateResolved();

        $result = $this->controller->update($request, $courier);

        $this->assertNull($result->resource->registered_at);
    }

    public function test_destroy_deletes_courier_and_returns_no_content(): void
    {
        $courier = Courier::factory()->create();

        $response = $this->controller->destroy($courier);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertDatabaseMissing('couriers', ['id' => $courier->id]);
    }
}
