<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiRequest
{
    /**
     * Convert request input keys from snake_case to camelCase
     */
    public static function convertToCamelCase(Request $request): array
    {
        return self::convertArrayKeysToCamelCase($request->all());
    }

    /**
     * Get a specific input value after converting to camelCase
     */
    public static function get(Request $request, string $key, mixed $default = null): mixed
    {
        $data = self::convertToCamelCase($request);
        $camelKey = Str::camel($key);

        return $data[$camelKey] ?? $default;
    }

    /**
     * Convert array keys to camelCase recursively
     */
    private static function convertArrayKeysToCamelCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = is_string($key) ? Str::camel($key) : $key;

            if (is_array($value)) {
                $result[$camelKey] = self::convertArrayKeysToCamelCase($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convert request input keys from camelCase to snake_case
     */
    public static function convertToSnakeCase(Request $request): array
    {
        return self::convertArrayKeysToSnakeCase($request->all());
    }

    /**
     * Convert array keys to snake_case recursively
     */
    private static function convertArrayKeysToSnakeCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $snakeKey = is_string($key) ? Str::snake($key) : $key;

            if (is_array($value)) {
                $result[$snakeKey] = self::convertArrayKeysToSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Validate request with snake_case error keys
     */
    public static function validate(Request $request, array $rules, array $messages = []): array
    {
        // Convert rules from snake_case to match incoming camelCase data
        $convertedRules = self::convertArrayKeysToSnakeCase($rules);

        // Validate
        $validated = $request->validate($convertedRules, $messages);

        // Return validated data in camelCase
        return self::convertArrayKeysToCamelCase($validated);
    }
}
