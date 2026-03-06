<?php

namespace App\Providers;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Messaging\RabbitMQBroker;
use App\Messaging\KafkaBroker;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, fn ($app) => new OrderRepository($app->make(\App\Models\Order::class)));
        $this->app->bind(OrderItemRepository::class, fn ($app) => new OrderItemRepository($app->make(\App\Models\OrderItem::class)));

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
