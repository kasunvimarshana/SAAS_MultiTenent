<?php

namespace App\Providers;

use App\Events\InventoryUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InventoryUpdated::class => [],
    ];

    public function boot(): void {}
}
