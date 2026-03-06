<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cross-service client for accessing Product Service data.
 */
class ProductServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.product_service.url', env('PRODUCT_SERVICE_URL', 'http://product-service:8002')), '/');
    }

    public function getProduct(int|string $productId, ?string $token = null): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->timeout(5)
                ->get("{$this->baseUrl}/api/products/{$productId}");

            if ($response->successful()) {
                return $response->json('data');
            }
            Log::warning('ProductServiceClient: Product not found', ['product_id' => $productId, 'status' => $response->status()]);
            return null;
        } catch (\Throwable $e) {
            Log::error('ProductServiceClient: Request failed', ['product_id' => $productId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function searchProducts(string $query, int|string $tenantId, ?string $token = null): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders($token))
                ->withHeader('X-Tenant-ID', (string) $tenantId)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/products/search/query", ['q' => $query]);

            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('ProductServiceClient: Search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getHeaders(?string $token): array
    {
        $headers = ['Accept' => 'application/json'];
        if ($token) {
            $headers['Authorization'] = "Bearer {$token}";
        }
        return $headers;
    }
}
