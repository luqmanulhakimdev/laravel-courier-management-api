<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreCourierRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCourierRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $request = new StoreCourierRequest;
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_expected_validation_rules(): void
    {
        $request = new StoreCourierRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayHasKey('level', $rules);
        $this->assertArrayHasKey('registered_at', $rules);
    }

    public function test_after_hook_rejects_unallowed_fields(): void
    {
        $data = [
            'name' => 'Budi',
            'phone' => '08123456789',
            'level' => 2,
            'unexpected_field' => 'forbidden',
        ];

        $request = StoreCourierRequest::create('/api/couriers', 'POST', $data);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        foreach ($request->after() as $afterHook) {
            $afterHook($validator);
        }

        $this->assertTrue($validator->errors()->has('unexpected_field'));
        $this->assertSame('This field is not allowed.', $validator->errors()->first('unexpected_field'));
    }

    public function test_after_hook_accepts_allowed_fields(): void
    {
        $data = [
            'name' => 'Budi',
            'phone' => '08123456789',
            'level' => 2,
        ];

        $request = StoreCourierRequest::create('/api/couriers', 'POST', $data);
        $request->setContainer($this->app);

        $validator = Validator::make($request->all(), $request->rules());
        foreach ($request->after() as $afterHook) {
            $afterHook($validator);
        }

        $this->assertFalse($validator->errors()->has('unexpected_field'));
    }
}
