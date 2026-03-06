<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends BaseController
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $tenant = app('current_tenant');
            $params = $request->only(['per_page', 'page', 'search', 'sort_by', 'sort_direction', 'filters']);
            $orders = $this->orderService->getOrdersByTenant($tenant->id, $params);
            return $this->paginatedResponse($orders, 'Orders retrieved successfully.');
        } catch (\Throwable $e) {
            Log::error('OrderController: index failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to retrieve orders.', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|integer',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer',
                'items.*.product_name' => 'required|string',
                'items.*.product_sku' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'shipping_address' => 'nullable|array',
                'billing_address' => 'nullable|array',
                'notes' => 'nullable|string',
            ]);

            $data['tenant_id'] = app('current_tenant')->id;
            $token = $request->bearerToken();

            $result = $this->orderService->createOrder($data, $token);

            if ($result->isSuccess()) {
                return $this->successResponse($result->context, 'Order created successfully.', 201);
            }

            return $this->errorResponse('Order creation failed: ' . $result->message, 422);
        } catch (\Throwable $e) {
            Log::error('OrderController: store failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 500);
        }
    }

    public function show(int|string $id): JsonResponse
    {
        try {
            $order = $this->orderService->getById($id);
            if (!$order) { return $this->errorResponse('Order not found.', 404); }
            return $this->successResponse($order->load('items'));
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to retrieve order.', 500);
        }
    }

    public function cancel(int|string $id): JsonResponse
    {
        try {
            $order = $this->orderService->cancelOrder($id);
            return $this->successResponse($order, 'Order cancelled successfully.');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to cancel order: ' . $e->getMessage(), 422);
        }
    }
}
