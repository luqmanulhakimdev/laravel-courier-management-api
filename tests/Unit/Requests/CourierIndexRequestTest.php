<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\CourierIndexRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CourierIndexRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new CourierIndexRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_prepare_for_validation_converts_level_string_to_array(): void
    {
        $request = CourierIndexRequest::create('/api/couriers?level=1,2,3', 'GET', ['level' => '1, 2, 3']);
        $request->setContainer($this->app);

        // Reflection to invoke protected prepareForValidation
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals(['1', '2', '3'], $request->input('level'));
    }

    public function test_prepare_for_validation_leaves_non_string_level_unchanged(): void
    {
        $request = CourierIndexRequest::create('/api/couriers', 'GET', ['level' => [1, 2]]);
        $request->setContainer($this->app);

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals([1, 2], $request->input('level'));
    }

    public function test_rules_returns_expected_array(): void
    {
        $request = new CourierIndexRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('level', $rules);
        $this->assertArrayHasKey('level.*', $rules);
        $this->assertArrayHasKey('sort_by', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertArrayHasKey('page', $rules);
    }

    public function test_after_hook_adds_error_for_unsupported_parameters(): void
    {
        $request = CourierIndexRequest::create('/api/couriers?unknown_filter=val&another_bad=1', 'GET', [
            'unknown_filter' => 'val',
            'another_bad' => '1',
        ]);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        foreach ($request->after() as $afterHook) {
            $afterHook($validator);
        }

        $errors = $validator->errors();
        $this->assertTrue($errors->has('unknown_filter'));
        $this->assertTrue($errors->has('another_bad'));
        $this->assertSame('The unknown_filter parameter is not supported.', $errors->first('unknown_filter'));
    }

    public function test_after_hook_does_not_add_error_for_allowed_parameters(): void
    {
        $request = CourierIndexRequest::create('/api/couriers?search=Budi&sort_by=registered_at&per_page=10&page=1', 'GET', [
            'search' => 'Budi',
            'sort_by' => 'registered_at',
            'per_page' => 10,
            'page' => 1,
        ]);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        foreach ($request->after() as $afterHook) {
            $afterHook($validator);
        }

        $this->assertFalse($validator->errors()->has('search'));
        $this->assertFalse($validator->errors()->has('sort_by'));
        $this->assertFalse($validator->errors()->has('per_page'));
        $this->assertFalse($validator->errors()->has('page'));
    }
}
