<?php

namespace App\Saga;

class SagaResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $context,
    ) {}

    public static function success(array $context, string $message = 'Saga completed successfully'): self
    {
        return new self(true, $message, $context);
    }

    public static function failure(string $message, array $context = []): self
    {
        return new self(false, $message, $context);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
