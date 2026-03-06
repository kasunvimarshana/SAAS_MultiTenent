<?php

namespace App\Services;

use App\DTOs\WebhookPayloadDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;

class WebhookService
{
    public function triggerWebhook(int|string $tenantId, string $event, array $data): void
    {
        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant || empty($tenant->webhook_url)) {
                return;
            }

            $payload = WebhookPayloadDTO::create($event, $data, $tenantId);
            $signature = $this->generateSignature($payload->toArray());

            Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $event,
                'X-Tenant-ID' => (string) $tenantId,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($tenant->webhook_url, $payload->toArray());

            Log::info('WebhookService: Webhook triggered', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'url' => $tenant->webhook_url,
            ]);
        } catch (\Throwable $e) {
            Log::error('WebhookService: Webhook failed', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateSignature(array $payload): string
    {
        $secret = config('app.webhook_secret', env('WEBHOOK_SECRET', ''));
        if (empty($secret)) {
            Log::critical('WebhookService: WEBHOOK_SECRET is not configured. Outbound webhook signatures are insecure.');
        }
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
