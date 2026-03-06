<?php

namespace App\Contracts\Messaging;

interface MessageBrokerInterface
{
    public function publish(string $topic, array $message, array $options = []): bool;
    public function subscribe(string $topic, callable $handler, array $options = []): void;
    public function connect(): void;
    public function disconnect(): void;
    public function isConnected(): bool;
    public function acknowledge(mixed $message): void;
    public function reject(mixed $message, bool $requeue = false): void;
}
