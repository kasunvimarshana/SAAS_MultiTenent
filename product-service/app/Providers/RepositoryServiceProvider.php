<?php

namespace App\Providers;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Messaging\RabbitMQBroker;
use App\Messaging\KafkaBroker;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductRepository::class, fn ($app) => new ProductRepository($app->make(\App\Models\Product::class)));
        $this->app->bind(CategoryRepository::class, fn ($app) => new CategoryRepository($app->make(\App\Models\Category::class)));

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
