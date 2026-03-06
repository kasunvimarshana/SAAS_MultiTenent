<?php

namespace App\Providers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Listeners\NotifyInventoryOnProductDeleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProductCreated::class => [],
        ProductDeleted::class => [
            NotifyInventoryOnProductDeleted::class,
        ],
    ];

    public function boot(): void {}
}
