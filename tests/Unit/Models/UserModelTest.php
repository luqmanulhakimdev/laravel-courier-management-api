<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_expected_fillable_attributes(): void
    {
        $user = new User;
        $this->assertEquals(['name', 'email', 'password'], $user->getFillable());
    }

    public function test_user_hides_sensitive_attributes_on_serialization(): void
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
        $user->remember_token = 'token123';

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function test_user_casts_attributes_correctly(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2026-01-01 12:00:00',
            'password' => 'plainpassword',
        ]);

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    public function test_user_factory_unverified_state(): void
    {
        $user = User::factory()->unverified()->make();

        $this->assertNull($user->email_verified_at);
    }
}
