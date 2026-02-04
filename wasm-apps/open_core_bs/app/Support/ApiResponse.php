<?php

namespace App\Support;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ApiResponse
{
    /**
     * Success response with data
     */
    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = self::convertKeysToSnakeCase($data);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    public static function error(string $message = 'An error occurred', int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $response['errors'] = self::convertKeysToSnakeCase($errors);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(Validator $validator, string $message = 'Validation failed'): JsonResponse
    {
        return self::error(
            message: $message,
            statusCode: 422,
            errors: $validator->errors()->toArray()
        );
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Created response
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * No content response
     */
    public static function noContent(string $message = 'No content'): JsonResponse
    {
        return self::success(null, $message, 204);
    }

    /**
     * Paginated response
     */
    public static function paginated($paginator, string $message = 'Success'): JsonResponse
    {
        $data = [
            'items' => self::convertKeysToSnakeCase($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        return self::success($data, $message);
    }

    /**
     * Collection response
     */
    public static function collection($items, ?int $total = null, string $message = 'Success'): JsonResponse
    {
        $data = [
            'items' => self::convertKeysToSnakeCase($items),
        ];

        if ($total !== null) {
            $data['total'] = $total;
        }

        return self::success($data, $message);
    }

    /**
     * Convert array/object keys to snake_case recursively
     */
    private static function convertKeysToSnakeCase(mixed $data): mixed
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $snakeKey = is_string($key) ? Str::snake($key) : $key;
                $result[$snakeKey] = self::convertKeysToSnakeCase($value);
            }

            return $result;
        }

        if (is_object($data)) {
            // Handle Laravel collections
            if (method_exists($data, 'toArray')) {
                return self::convertKeysToSnakeCase($data->toArray());
            }

            // Handle stdClass and other objects
            $result = [];
            foreach (get_object_vars($data) as $key => $value) {
                $snakeKey = Str::snake($key);
                $result[$snakeKey] = self::convertKeysToSnakeCase($value);
            }

            return $result;
        }

        return $data;
    }

    /**
     * Convert keys from snake_case to camelCase
     */
    public static function convertKeysToCamelCase(mixed $data): mixed
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $camelKey = is_string($key) ? Str::camel($key) : $key;
                $result[$camelKey] = self::convertKeysToCamelCase($value);
            }

            return $result;
        }

        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return self::convertKeysToCamelCase($data->toArray());
            }

            $result = [];
            foreach (get_object_vars($data) as $key => $value) {
                $camelKey = Str::camel($key);
                $result[$camelKey] = self::convertKeysToCamelCase($value);
            }

            return $result;
        }

        return $data;
    }
}
