<?php

namespace App\Providers;

use App\Events\OrderCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [],
    ];

    public function boot(): void {}
}
