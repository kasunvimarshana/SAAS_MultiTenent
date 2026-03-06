<?php

namespace App\Saga\Steps;

use App\Saga\SagaStep;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Log;

class ConfirmOrderStep extends SagaStep
{
    public function __construct(private readonly OrderRepository $orderRepository) {}

    public function getName(): string
    {
        return 'confirm_order';
    }

    public function execute(array &$context): array
    {
        $orderId = $context['order_id'];
        $paymentId = $context['payment_id'] ?? null;

        $order = $this->orderRepository->update($orderId, [
            'status' => 'confirmed',
            'payment_id' => $paymentId,
            'payment_status' => $context['payment_status'] ?? 'completed',
            'confirmed_at' => now(),
        ]);

        Log::info("ConfirmOrderStep: Order confirmed", ['order_id' => $orderId]);

        return ['order_status' => 'confirmed'];
    }

    public function compensate(array $context): void
    {
        $orderId = $context['order_id'];

        try {
            $this->orderRepository->update($orderId, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Saga rollback',
            ]);
            Log::info("ConfirmOrderStep: Compensated (cancelled) order", ['order_id' => $orderId]);
        } catch (\Throwable $e) {
            Log::error("ConfirmOrderStep: Compensation failed", ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }
}
