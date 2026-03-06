<?php

namespace App\Saga\Steps;

use App\Saga\SagaStep;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPaymentStep extends SagaStep
{
    public function getName(): string
    {
        return 'process_payment';
    }

    public function execute(array &$context): array
    {
        $orderId = $context['order_id'];
        $amount = $context['total_amount'];
        $tenantId = $context['tenant_id'];

        // In a real implementation, call a payment gateway (Stripe, PayPal, etc.)
        // Here we simulate a successful payment
        $paymentId = 'PAY-' . Str::upper(Str::random(10));

        Log::info("ProcessPaymentStep: Payment processed", [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'tenant_id' => $tenantId,
        ]);

        return [
            'payment_id' => $paymentId,
            'payment_status' => 'completed',
        ];
    }

    public function compensate(array $context): void
    {
        $paymentId = $context['payment_id'] ?? null;
        $orderId = $context['order_id'];

        if ($paymentId) {
            // In real implementation: call payment gateway to refund/void
            Log::info("ProcessPaymentStep: Compensated (refunded) payment", [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);
        }
    }
}
