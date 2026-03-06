<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cross-service client for the Inventory Service.
 */
class InventoryServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.inventory_service.url', env('INVENTORY_SERVICE_URL', 'http://inventory-service:8003')), '/');
    }

    public function reserveStock(int|string $productId, int $quantity, int|string $tenantId, ?string $token = null): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token, $tenantId))
                ->timeout(10)
                ->post("{$this->baseUrl}/api/inventory/reserve", [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'tenant_id' => $tenantId,
                ]);

            if ($response->successful()) {
                Log::info('InventoryServiceClient: Stock reserved', ['product_id' => $productId, 'quantity' => $quantity]);
                return true;
            }

            throw new \RuntimeException("Reserve stock failed: " . $response->body());
        } catch (\Throwable $e) {
            Log::error('InventoryServiceClient: reserveStock failed', ['product_id' => $productId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function releaseStock(int|string $productId, int $quantity, int|string $tenantId, ?string $token = null): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token, $tenantId))
                ->timeout(10)
                ->post("{$this->baseUrl}/api/inventory/release", [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'tenant_id' => $tenantId,
                ]);

            Log::info('InventoryServiceClient: Stock released', ['product_id' => $productId, 'quantity' => $quantity]);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('InventoryServiceClient: releaseStock failed', ['product_id' => $productId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function getHeaders(?string $token, int|string $tenantId): array
    {
        $headers = ['Accept' => 'application/json', 'X-Tenant-ID' => (string) $tenantId];
        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }
        return $headers;
    }
}
