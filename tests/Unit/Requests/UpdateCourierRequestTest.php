<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateCourierRequest;
use App\Models\Courier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateCourierRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $request = new UpdateCourierRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_rules_for_put_method_sets_required(): void
    {
        $courier = Courier::factory()->create();
        $request = UpdateCourierRequest::create('/api/couriers/'.$courier->id, 'PUT');
        $route = new Route('PUT', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', $courier);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);

        $rules = $request->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('required', $rules['phone']);
        $this->assertContains('required', $rules['level']);
    }

    public function test_rules_for_patch_method_sets_sometimes(): void
    {
        $courier = Courier::factory()->create();
        $request = UpdateCourierRequest::create('/api/couriers/'.$courier->id, 'PATCH');
        $route = new Route('PATCH', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', $courier);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);

        $rules = $request->rules();

        $this->assertContains('sometimes', $rules['name']);
        $this->assertContains('sometimes', $rules['phone']);
        $this->assertContains('sometimes', $rules['level']);
    }

    public function test_rules_resolves_courier_id_when_route_parameter_is_scalar(): void
    {
        $request = UpdateCourierRequest::create('/api/couriers/123', 'PATCH');
        $route = new Route('PATCH', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', 123);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);

        $rules = $request->rules();
        $this->assertArrayHasKey('email', $rules);
    }

    public function test_after_hook_rejects_unallowed_fields(): void
    {
        $courier = Courier::factory()->create();
        $request = UpdateCourierRequest::create('/api/couriers/'.$courier->id, 'PATCH', [
            'name' => 'New Name',
            'unsupported_field' => 'bad',
        ]);
        $route = new Route('PATCH', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', $courier);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        foreach ($request->after() as $afterHook) {
            $afterHook($validator);
        }

        $this->assertTrue($validator->errors()->has('unsupported_field'));
        $this->assertSame('This field is not allowed.', $validator->errors()->first('unsupported_field'));
    }

    public function test_after_hook_passes_when_all_fields_are_allowed(): void
    {
        $courier = Courier::factory()->create();
        $request = UpdateCourierRequest::create('/api/couriers/'.$courier->id, 'PATCH', [
            'name' => 'New Name',
        ]);
        $route = new Route('PATCH', 'api/couriers/{courier}', []);
        $route->bind($request);
        $route->setParameter('courier', $courier);
        $request->setRouteResolver(fn () => $route);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        foreach ($request->after() as $afterHook) {
            $afterHook($validator);
        }

        $this->assertFalse($validator->errors()->has('name'));
    }
}
