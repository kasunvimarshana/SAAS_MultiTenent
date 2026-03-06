<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function successResponse(mixed $data, string $message = 'Success', int $statusCode = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $statusCode);
    }

    protected function errorResponse(string $message, int $statusCode = 400, mixed $errors = null): \Illuminate\Http\JsonResponse
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors !== null) { $response['errors'] = $errors; }
        return response()->json($response, $statusCode);
    }

    protected function paginatedResponse(mixed $data, string $message = 'Success'): \Illuminate\Http\JsonResponse
    {
        if ($data instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return response()->json([
                'success' => true, 'message' => $message,
                'data' => $data->items(),
                'pagination' => ['current_page' => $data->currentPage(), 'last_page' => $data->lastPage(), 'per_page' => $data->perPage(), 'total' => $data->total()],
            ]);
        }
        return $this->successResponse($data, $message);
    }
}
