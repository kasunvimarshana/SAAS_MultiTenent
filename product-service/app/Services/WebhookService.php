<?php

namespace App\Services;

use App\DTOs\WebhookPayloadDTO;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            $signature = hash_hmac('sha256', json_encode($payload->toArray()), config('app.webhook_secret', env('WEBHOOK_SECRET', '')));
            Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $event,
                'X-Tenant-ID' => (string) $tenantId,
            ])->timeout(10)->post($tenant->webhook_url, $payload->toArray());
        } catch (\Throwable $e) {
            Log::error('WebhookService: Failed', ['tenant_id' => $tenantId, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
