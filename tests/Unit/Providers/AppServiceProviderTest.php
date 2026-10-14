<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_register_and_boot(): void
    {
        $provider = new AppServiceProvider($this->app);

        // Assert register and boot run without exceptions
        $provider->register();
        $provider->boot();

        $this->assertTrue(true);
    }
}
