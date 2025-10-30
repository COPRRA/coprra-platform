<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertEmptyStringsToNull
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $input = $request->all();
        $convertedInput = $this->convertEmptyStringsToNull($input);
        $request->merge($convertedInput);

        $response = $next($request);
        if (! $response instanceof Response) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    /**
     * Convert empty strings to null recursively.
     */
    private function convertEmptyStringsToNull(array $input): array
    {
        foreach ($input as $key => $value) {
            if (\is_string($value) && '' === $value) {
                $input[$key] = null;
            } elseif (\is_array($value)) {
                $input[$key] = $this->convertEmptyStringsToNull($value);
            }
        }

        return $input;
    }
}
