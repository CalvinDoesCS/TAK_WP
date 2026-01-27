<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConvertRequestKeysMiddleware
{
    /**
     * Handle an incoming request and convert snake_case keys to camelCase
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Convert incoming request keys from snake_case to camelCase
        $request->replace($this->convertKeysToCase($request->all(), 'camel'));

        $response = $next($request);

        // Convert outgoing response keys to snake_case
        if ($response->headers->get('Content-Type') === 'application/json' ||
            str_contains($response->headers->get('Content-Type') ?? '', 'application/json')) {
            $content = json_decode($response->getContent(), true);

            if (is_array($content)) {
                $response->setContent(json_encode(
                    $this->convertKeysToCase($content, 'snake')
                ));
            }
        }

        return $response;
    }

    /**
     * Convert array keys recursively
     */
    private function convertKeysToCase(array $data, string $case): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = is_string($key)
                ? ($case === 'camel' ? Str::camel($key) : Str::snake($key))
                : $key;

            if (is_array($value)) {
                $result[$newKey] = $this->convertKeysToCase($value, $case);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
