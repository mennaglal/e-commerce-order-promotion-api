<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;


class ApiResponse
{

    private const FAILURE_STATUS = [
        'cart_empty'                        => 422,
        'insufficient_stock'                => 409,
        'product_unavailable'               => 409,
        'invalid_order_status'              => 409,
        'coupon_invalid'                    => 422,
        'coupon_not_started'                => 422,
        'coupon_expired'                    => 422,
        'coupon_min_amount'                 => 422,
        'coupon_usage_limit_reached'        => 422,
        'coupon_per_customer_limit_reached' => 422,
    ];

    public static function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $resourceClass::collection($paginator->items())->resolve(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null, ?string $code = null): JsonResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($code !== null) {
            $body['code'] = $code;
        }

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }


    public static function failure(array $result): JsonResponse
    {
        $errors = $result['errors'] ?? [];
        $code   = Arr::pull($errors, 'code');

        return self::error(
            $result['message'] ?? 'Request failed.',
            self::FAILURE_STATUS[$code] ?? 422,
            $errors ?: null,
            $code
        );
    }
}
