<?php

namespace App\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Events\InventoryUpdated;
use App\Repositories\InventoryRepository;
use App\Repositories\InventoryTransactionRepository;
use App\Services\ProductServiceClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class InventoryService extends BaseService
{
    public function __construct(
        protected readonly InventoryRepository $repository,
        private readonly InventoryTransactionRepository $transactionRepository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly WebhookService $webhookService,
        private readonly ProductServiceClient $productClient
    ) {
        parent::__construct($repository);
    }

    public function getInventoryByTenant(int|string $tenantId, array $params = []): mixed
    {
        $params['filters']['tenant_id'] = $tenantId;
        return $this->repository->conditionalPaginate($params);
    }

    /**
     * Get inventory with enriched product details (cross-service data access).
     */
    public function getInventoryWithProductDetails(int|string $tenantId, array $params = []): mixed
    {
        $params['filters']['tenant_id'] = $tenantId;
        $inventoryItems = $this->repository->conditionalPaginate($params);

        // Enrich with product details from product-service
        $items = is_a($inventoryItems, \Illuminate\Pagination\LengthAwarePaginator::class)
            ? collect($inventoryItems->items())
            : $inventoryItems;

        $enriched = $items->map(function ($item) {
            try {
                $productDetails = $this->productClient->getProduct($item->product_id);
                if ($productDetails) {
                    $item->product_details = $productDetails;
                }
            } catch (\Throwable $e) {
                Log::warning('InventoryService: Could not fetch product details', [
                    'product_id' => $item->product_id,
                    'error' => $e->getMessage(),
                ]);
            }
            return $item;
        });

        if (is_a($inventoryItems, \Illuminate\Pagination\LengthAwarePaginator::class)) {
            $inventoryItems->setCollection($enriched);
        }

        return $inventoryItems;
    }

    /**
     * Filter inventory by product name (cross-service search).
     */
    public function filterByProductName(string $productName, int|string $tenantId): Collection
    {
        return $this->repository->searchByProductName($productName, $tenantId);
    }

    public function addStock(int|string $inventoryId, int $quantity, string $notes = '', ?int $performedBy = null): Model
    {
        return $this->repository->transaction(function () use ($inventoryId, $quantity, $notes, $performedBy) {
            $inventory = $this->repository->find($inventoryId);
            if (!$inventory) {
                throw new \RuntimeException("Inventory item not found: {$inventoryId}");
            }

            $previousQty = $inventory->quantity;
            $updated = $this->repository->adjustQuantity($inventoryId, $quantity);

            // Record transaction
            $this->transactionRepository->create([
                'tenant_id' => $inventory->tenant_id,
                'inventory_id' => $inventoryId,
                'type' => 'stock_in',
                'quantity' => $quantity,
                'previous_quantity' => $previousQty,
                'new_quantity' => $updated->quantity,
                'notes' => $notes,
                'performed_by' => $performedBy,
            ]);

            Event::dispatch(new InventoryUpdated($updated, 'stock_in', $quantity));

            $this->messageBroker->publish('inventory.updated', [
                'inventory_id' => $inventoryId,
                'tenant_id' => $inventory->tenant_id,
                'product_id' => $inventory->product_id,
                'type' => 'stock_in',
                'quantity' => $quantity,
                'new_quantity' => $updated->quantity,
            ]);

            return $updated;
        });
    }

    public function removeStock(int|string $inventoryId, int $quantity, string $notes = '', ?int $performedBy = null): Model
    {
        return $this->repository->transaction(function () use ($inventoryId, $quantity, $notes, $performedBy) {
            $inventory = $this->repository->find($inventoryId);
            if (!$inventory) {
                throw new \RuntimeException("Inventory item not found: {$inventoryId}");
            }

            $previousQty = $inventory->quantity;
            $updated = $this->repository->adjustQuantity($inventoryId, -$quantity);

            $this->transactionRepository->create([
                'tenant_id' => $inventory->tenant_id,
                'inventory_id' => $inventoryId,
                'type' => 'stock_out',
                'quantity' => $quantity,
                'previous_quantity' => $previousQty,
                'new_quantity' => $updated->quantity,
                'notes' => $notes,
                'performed_by' => $performedBy,
            ]);

            Event::dispatch(new InventoryUpdated($updated, 'stock_out', $quantity));

            $this->messageBroker->publish('inventory.updated', [
                'inventory_id' => $inventoryId,
                'tenant_id' => $inventory->tenant_id,
                'product_id' => $inventory->product_id,
                'type' => 'stock_out',
                'quantity' => $quantity,
                'new_quantity' => $updated->quantity,
            ]);

            return $updated;
        });
    }

    public function reserveStock(int|string $productId, int $quantity, int|string $tenantId): bool
    {
        $inventory = $this->repository->findByTenantAndProduct($tenantId, $productId);
        if (!$inventory) {
            throw new \RuntimeException("No inventory found for product {$productId}");
        }
        return $this->repository->reserveQuantity($inventory->id, $quantity);
    }

    public function releaseStock(int|string $productId, int $quantity, int|string $tenantId): bool
    {
        $inventory = $this->repository->findByTenantAndProduct($tenantId, $productId);
        if (!$inventory) {
            return false;
        }
        return $this->repository->releaseReservation($inventory->id, $quantity);
    }

    public function getLowStockItems(int|string $tenantId): Collection
    {
        return $this->repository->getLowStockItems($tenantId);
    }
}
