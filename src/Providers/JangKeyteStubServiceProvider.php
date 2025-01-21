<?php

namespace Modules\JangKeyte\src\Providers;

use Modules\JangKeyte\src\Stubs\JangKeyteStub;
use Illuminate\Support\ServiceProvider;

class JangKeyteStubServiceProvider extends ServiceProvider
{
    /**
     * Boot providers.
     */
    public function boot(): void
    {
        $this->app->bind('jangkeyte-stub', fn ($app) => new JangKeyteStub);
    }
}