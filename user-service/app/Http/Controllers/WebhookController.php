<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseController
{
    public function handle(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-Webhook-Signature');
            $event = $request->header('X-Webhook-Event');
            $tenantId = $request->header('X-Tenant-ID');

            if (!$this->verifySignature($request, $signature)) {
                return $this->errorResponse('Invalid webhook signature.', 401);
            }

            Log::info('WebhookController: Received webhook', [
                'event' => $event,
                'tenant_id' => $tenantId,
                'payload' => $request->all(),
            ]);

            $this->processWebhookEvent($event, $request->all(), $tenantId);

            return $this->successResponse(null, 'Webhook processed successfully.');
        } catch (\Throwable $e) {
            Log::error('WebhookController: Processing failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Webhook processing failed.', 500);
        }
    }

    private function verifySignature(Request $request, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }
        $secret = config('app.webhook_secret', env('WEBHOOK_SECRET', ''));
        if (empty($secret)) {
            Log::critical('WebhookController: WEBHOOK_SECRET is not configured. Rejecting all incoming webhooks.');
            return false;
        }
        $expected = hash_hmac('sha256', json_encode($request->all()), $secret);
        return hash_equals($expected, $signature);
    }

    private function processWebhookEvent(string $event, array $payload, ?string $tenantId): void
    {
        // Dispatch appropriate events/jobs based on webhook event type
        match ($event) {
            'user.created' => Log::info('Processing user.created webhook', $payload),
            'payment.completed' => Log::info('Processing payment.completed webhook', $payload),
            default => Log::info("Unknown webhook event: {$event}", $payload),
        };
    }
}
