<?php

namespace App\Listeners;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Events\ProductDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyInventoryOnProductDeleted implements ShouldQueue
{
    public function __construct(private readonly MessageBrokerInterface $messageBroker) {}

    public function handle(ProductDeleted $event): void
    {
        try {
            $this->messageBroker->publish('product.deleted', [
                'product_id' => $event->product->id,
                'tenant_id' => $event->product->tenant_id,
                'sku' => $event->product->sku,
            ]);
            Log::info('NotifyInventoryOnProductDeleted: Event published', ['product_id' => $event->product->id]);
        } catch (\Throwable $e) {
            Log::error('NotifyInventoryOnProductDeleted: Failed', ['error' => $e->getMessage()]);
        }
    }
}
