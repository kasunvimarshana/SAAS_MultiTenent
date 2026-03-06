<?php

namespace App\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Repositories\ProductRepository;
use App\DTOs\ProductDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class ProductService extends BaseService
{
    public function __construct(
        protected readonly ProductRepository $repository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly WebhookService $webhookService
    ) {
        parent::__construct($repository);
    }

    public function createProduct(array $data): Model
    {
        return $this->repository->transaction(function () use ($data) {
            $product = $this->repository->create($data);

            // Dispatch domain event
            Event::dispatch(new ProductCreated($product));

            // Publish to message broker
            $this->messageBroker->publish('product.created', [
                'product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
            ]);

            // Trigger webhook
            $this->webhookService->triggerWebhook($product->tenant_id, 'product.created', [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ]);

            Log::info('ProductService: Product created', ['product_id' => $product->id]);
            return $product;
        });
    }

    public function updateProduct(int|string $id, array $data): Model
    {
        return $this->repository->transaction(function () use ($id, $data) {
            $product = $this->repository->update($id, $data);

            $this->messageBroker->publish('product.updated', [
                'product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'changes' => $data,
            ]);

            return $product;
        });
    }

    public function deleteProduct(int|string $id): bool
    {
        return $this->repository->transaction(function () use ($id) {
            $product = $this->repository->find($id);
            if (!$product) {
                return false;
            }

            $result = $this->repository->delete($id);

            Event::dispatch(new ProductDeleted($product));

            $this->messageBroker->publish('product.deleted', [
                'product_id' => $product->id,
                'tenant_id' => $product->tenant_id,
            ]);

            return $result;
        });
    }

    public function getProductsByTenant(int|string $tenantId, array $params = []): mixed
    {
        $params['filters']['tenant_id'] = $tenantId;
        return $this->repository->conditionalPaginate($params);
    }

    public function searchProducts(string $query, int|string $tenantId): \Illuminate\Support\Collection
    {
        return $this->repository->searchByName($query, $tenantId);
    }

    public function getProductDTO(int|string $productId): ?ProductDTO
    {
        $product = $this->repository->find($productId);
        return $product ? ProductDTO::fromModel($product) : null;
    }
}
