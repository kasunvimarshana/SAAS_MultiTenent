<?php

namespace App\Services;

use App\Contracts\Messaging\MessageBrokerInterface;
use App\Events\OrderCreated;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Saga\SagaOrchestrator;
use App\Saga\SagaResult;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ConfirmOrderStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderService extends BaseService
{
    public function __construct(
        protected readonly OrderRepository $repository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly MessageBrokerInterface $messageBroker,
        private readonly WebhookService $webhookService,
        private readonly InventoryServiceClient $inventoryClient
    ) {
        parent::__construct($repository);
    }

    /**
     * Create order using the Saga pattern for distributed transaction management.
     */
    public function createOrder(array $data, ?string $authToken = null): SagaResult
    {
        return $this->repository->transaction(function () use ($data, $authToken) {
            // Calculate totals
            $items = $data['items'];
            $totalAmount = array_sum(array_map(fn ($item) => $item['unit_price'] * $item['quantity'], $items));

            // Create the order in pending state
            $order = $this->repository->create([
                'tenant_id' => $data['tenant_id'],
                'user_id' => $data['user_id'],
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => $totalAmount,
                'currency' => $data['currency'] ?? 'USD',
                'shipping_address' => $data['shipping_address'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create order items
            foreach ($items as $item) {
                $this->orderItemRepository->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            // Execute Saga
            $saga = new SagaOrchestrator("order-{$order->id}");
            $saga->setContext('order_id', $order->id)
                ->setContext('tenant_id', $data['tenant_id'])
                ->setContext('order_items', $items)
                ->setContext('total_amount', $totalAmount)
                ->setContext('auth_token', $authToken);

            $saga->addStep(new ReserveInventoryStep($this->inventoryClient));
            $saga->addStep(new ProcessPaymentStep());
            $saga->addStep(new ConfirmOrderStep($this->repository));

            $result = $saga->execute();

            if ($result->isSuccess()) {
                $order->refresh();
                Event::dispatch(new OrderCreated($order));

                $this->messageBroker->publish('order.created', [
                    'order_id' => $order->id,
                    'tenant_id' => $order->tenant_id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                ]);

                $this->webhookService->triggerWebhook($order->tenant_id, 'order.created', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                ]);

                Log::info('OrderService: Order created successfully', ['order_id' => $order->id]);
            } else {
                Log::error('OrderService: Order saga failed', ['order_id' => $order->id, 'reason' => $result->message]);
            }

            return $result;
        });
    }

    public function getOrdersByTenant(int|string $tenantId, array $params = []): mixed
    {
        return $this->repository->getByTenant($tenantId, $params);
    }

    public function cancelOrder(int|string $orderId): Model
    {
        return $this->repository->transaction(function () use ($orderId) {
            $order = $this->repository->find($orderId);
            if (!$order) {
                throw new \RuntimeException("Order not found: {$orderId}");
            }
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new \RuntimeException("Cannot cancel order with status: {$order->status}");
            }

            // Release inventory reservations
            foreach ($order->items as $item) {
                try {
                    $this->inventoryClient->releaseStock($item->product_id, $item->quantity, $order->tenant_id);
                } catch (\Throwable $e) {
                    Log::warning('OrderService: Could not release inventory on cancel', [
                        'order_id' => $orderId,
                        'product_id' => $item->product_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $this->repository->update($orderId, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
        });
    }
}
