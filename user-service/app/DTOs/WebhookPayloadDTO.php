<?php

namespace App\DTOs;

class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $event,
        public readonly array $data,
        public readonly int|string $tenantId,
        public readonly string $timestamp,
        public readonly string $version = '1.0',
    ) {}

    public static function create(string $event, array $data, int|string $tenantId): self
    {
        return new self(
            event: $event,
            data: $data,
            tenantId: $tenantId,
            timestamp: now()->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'data' => $this->data,
            'tenant_id' => $this->tenantId,
            'timestamp' => $this->timestamp,
            'version' => $this->version,
        ];
    }
}
