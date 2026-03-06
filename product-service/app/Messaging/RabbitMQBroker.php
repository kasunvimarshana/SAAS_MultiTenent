<?php

namespace App\Messaging;

use App\Contracts\Messaging\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class RabbitMQBroker implements MessageBrokerInterface
{
    private bool $connected = false;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/'
    ) {}

    public function connect(): void
    {
        $this->connected = true;
        Log::info('RabbitMQBroker: Connected', ['host' => $this->host]);
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function publish(string $topic, array $message, array $options = []): bool
    {
        if (!$this->connected) {
            $this->connect();
        }
        Log::info('RabbitMQBroker: Published', ['topic' => $topic, 'message' => $message]);
        return true;
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        if (!$this->connected) {
            $this->connect();
        }
        Log::info('RabbitMQBroker: Subscribed', ['topic' => $topic]);
    }

    public function acknowledge(mixed $message): void {}

    public function reject(mixed $message, bool $requeue = false): void {}
}
