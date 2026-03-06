<?php

namespace App\Providers;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Messaging\RabbitMQBroker;
use App\Messaging\KafkaBroker;
use App\Repositories\InventoryRepository;
use App\Repositories\InventoryTransactionRepository;
use App\Repositories\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryRepository::class, fn ($app) => new InventoryRepository($app->make(\App\Models\Inventory::class)));
        $this->app->bind(InventoryTransactionRepository::class, fn ($app) => new InventoryTransactionRepository($app->make(\App\Models\InventoryTransaction::class)));
        $this->app->bind(WarehouseRepository::class, fn ($app) => new WarehouseRepository($app->make(\App\Models\Warehouse::class)));

        $this->app->bind(MessageBrokerInterface::class, function ($app) {
            $driver = config('messaging.driver', env('MESSAGE_BROKER_DRIVER', 'rabbitmq'));
            return match ($driver) {
                'kafka' => new KafkaBroker(
                    brokers: config('messaging.kafka.brokers', 'kafka:9092'),
                    groupId: config('messaging.kafka.group_id', 'saas-inventory'),
                ),
                default => new RabbitMQBroker(
                    host: config('messaging.rabbitmq.host', env('RABBITMQ_HOST', 'rabbitmq')),
                    port: (int) config('messaging.rabbitmq.port', env('RABBITMQ_PORT', 5672)),
                    user: config('messaging.rabbitmq.user', env('RABBITMQ_USER', 'guest')),
                    password: config('messaging.rabbitmq.password', env('RABBITMQ_PASSWORD', 'guest')),
                    vhost: config('messaging.rabbitmq.vhost', env('RABBITMQ_VHOST', '/')),
                ),
            };
        });
    }
}
