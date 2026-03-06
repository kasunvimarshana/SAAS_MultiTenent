<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends BaseController
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $tenant = app('current_tenant');
            $params = $request->only(['per_page', 'page', 'search', 'sort_by', 'sort_direction', 'filters']);
            $products = $this->productService->getProductsByTenant($tenant->id, $params);
            return $this->paginatedResponse($products, 'Products retrieved successfully.');
        } catch (\Throwable $e) {
            Log::error('ProductController: index failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve products.', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'required|string|max:100|unique:products,sku',
                'price' => 'required|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'category' => 'nullable|string|max:100',
                'brand' => 'nullable|string|max:100',
                'unit' => 'nullable|string|max:50',
                'image_url' => 'nullable|url',
                'attributes' => 'nullable|array',
            ]);
            $data['tenant_id'] = app('current_tenant')->id;
            $product = $this->productService->createProduct($data);
            return $this->successResponse($product, 'Product created successfully.', 201);
        } catch (\Throwable $e) {
            Log::error('ProductController: store failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create product: ' . $e->getMessage(), 500);
        }
    }

    public function show(int|string $id): JsonResponse
    {
        try {
            $product = $this->productService->getById($id);
            if (!$product) {
                return $this->errorResponse('Product not found.', 404);
            }
            return $this->successResponse($product);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve product.', 500);
        }
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'is_active' => 'sometimes|boolean',
                'attributes' => 'nullable|array',
            ]);
            $product = $this->productService->updateProduct($id, $data);
            return $this->successResponse($product, 'Product updated successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update product: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(int|string $id): JsonResponse
    {
        try {
            $deleted = $this->productService->deleteProduct($id);
            if (!$deleted) {
                return $this->errorResponse('Product not found.', 404);
            }
            return $this->successResponse(null, 'Product deleted successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to delete product.', 500);
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate(['q' => 'required|string|min:1']);
            $tenant = app('current_tenant');
            $products = $this->productService->searchProducts($request->input('q'), $tenant->id);
            return $this->successResponse($products, 'Search results.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage(), 500);
        }
    }
}
