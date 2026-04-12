<?php

use App\Providers\AppServiceProvider;
use App\Providers\RateLimitingServiceProvider;
use App\Providers\TenantServiceProvider;

return [
    AppServiceProvider::class,
    RateLimitingServiceProvider::class,
    TenantServiceProvider::class,
];
