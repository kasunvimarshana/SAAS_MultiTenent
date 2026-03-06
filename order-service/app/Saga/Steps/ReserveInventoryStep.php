<?php

namespace App\Saga\Steps;

use App\Saga\SagaStep;
use App\Services\InventoryServiceClient;
use Illuminate\Support\Facades\Log;

class ReserveInventoryStep extends SagaStep
{
    public function __construct(private readonly InventoryServiceClient $inventoryClient) {}

    public function getName(): string
    {
        return 'reserve_inventory';
    }

    public function execute(array &$context): array
    {
        $items = $context['order_items'] ?? [];
        $tenantId = $context['tenant_id'];
        $orderId = $context['order_id'];

        $reservedItems = [];
        foreach ($items as $item) {
            $this->inventoryClient->reserveStock(
                $item['product_id'],
                $item['quantity'],
                $tenantId,
                $context['auth_token'] ?? null
            );
            $reservedItems[] = $item;
        }

        Log::info("ReserveInventoryStep: Reserved inventory for order {$orderId}", ['items' => count($reservedItems)]);
        return ['reserved_items' => $reservedItems];
    }

    public function compensate(array $context): void
    {
        $items = $context['reserved_items'] ?? $context['order_items'] ?? [];
        $tenantId = $context['tenant_id'];
        $orderId = $context['order_id'];

        foreach ($items as $item) {
            try {
                $this->inventoryClient->releaseStock(
                    $item['product_id'],
                    $item['quantity'],
                    $tenantId,
                    $context['auth_token'] ?? null
                );
            } catch (\Throwable $e) {
                Log::error("ReserveInventoryStep: Compensation failed for product {$item['product_id']}", ['error' => $e->getMessage()]);
            }
        }

        Log::info("ReserveInventoryStep: Compensated (released) inventory for order {$orderId}");
    }
}
