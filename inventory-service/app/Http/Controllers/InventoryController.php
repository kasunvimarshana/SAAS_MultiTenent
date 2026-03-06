<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryController extends BaseController
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $tenant = app('current_tenant');
            $params = $request->only(['per_page', 'page', 'search', 'sort_by', 'sort_direction', 'filters']);
            $inventory = $this->inventoryService->getInventoryByTenant($tenant->id, $params);
            return $this->paginatedResponse($inventory, 'Inventory retrieved successfully.');
        } catch (\Throwable $e) {
            Log::error('InventoryController: index failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve inventory.', 500);
        }
    }

    public function indexWithProducts(Request $request): JsonResponse
    {
        try {
            $tenant = app('current_tenant');
            $params = $request->only(['per_page', 'page', 'search', 'sort_by', 'sort_direction']);
            $inventory = $this->inventoryService->getInventoryWithProductDetails($tenant->id, $params);
            return $this->paginatedResponse($inventory, 'Inventory with product details retrieved.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve inventory with product details.', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|integer',
                'product_name' => 'required|string|max:255',
                'product_sku' => 'required|string|max:100',
                'warehouse_id' => 'nullable|integer',
                'quantity' => 'required|integer|min:0',
                'reorder_level' => 'nullable|integer|min:0',
                'reorder_quantity' => 'nullable|integer|min:0',
                'location' => 'nullable|string|max:100',
                'unit' => 'nullable|string|max:50',
                'notes' => 'nullable|string',
            ]);
            $data['tenant_id'] = app('current_tenant')->id;
            $inventory = $this->inventoryService->create($data);
            return $this->successResponse($inventory, 'Inventory item created.', 201);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to create inventory: ' . $e->getMessage(), 500);
        }
    }

    public function show(int|string $id): JsonResponse
    {
        try {
            $inventory = $this->inventoryService->getById($id);
            if (!$inventory) { return $this->errorResponse('Inventory item not found.', 404); }
            return $this->successResponse($inventory);
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve inventory item.', 500);
        }
    }

    public function addStock(Request $request, int|string $id): JsonResponse
    {
        try {
            $request->validate(['quantity' => 'required|integer|min:1', 'notes' => 'nullable|string']);
            $inventory = $this->inventoryService->addStock(
                $id,
                $request->input('quantity'),
                $request->input('notes', ''),
                $request->user()?->id
            );
            return $this->successResponse($inventory, 'Stock added successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to add stock: ' . $e->getMessage(), 500);
        }
    }

    public function removeStock(Request $request, int|string $id): JsonResponse
    {
        try {
            $request->validate(['quantity' => 'required|integer|min:1', 'notes' => 'nullable|string']);
            $inventory = $this->inventoryService->removeStock(
                $id,
                $request->input('quantity'),
                $request->input('notes', ''),
                $request->user()?->id
            );
            return $this->successResponse($inventory, 'Stock removed successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to remove stock: ' . $e->getMessage(), 500);
        }
    }

    public function lowStock(Request $request): JsonResponse
    {
        try {
            $tenant = app('current_tenant');
            $items = $this->inventoryService->getLowStockItems($tenant->id);
            return $this->successResponse($items, 'Low stock items retrieved.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve low stock items.', 500);
        }
    }

    public function searchByProductName(Request $request): JsonResponse
    {
        try {
            $request->validate(['q' => 'required|string|min:1']);
            $tenant = app('current_tenant');
            $items = $this->inventoryService->filterByProductName($request->input('q'), $tenant->id);
            return $this->successResponse($items, 'Search results.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Search failed: ' . $e->getMessage(), 500);
        }
    }
}
