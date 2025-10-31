<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\Http;

trait HttpHelpers
{
    /**
     * Mock a successful HTTP response.
     */
    protected function mockHttpSuccess(array $data = [], int $status = 200): void
    {
        Http::fake([
            '*' => Http::response($data, $status),
        ]);
    }

    /**
     * Mock an HTTP error response.
     */
    protected function mockHttpError(int $status = 500, array $data = []): void
    {
        Http::fake([
            '*' => Http::response($data, $status),
        ]);
    }

    /**
     * Mock multiple HTTP endpoints.
     */
    protected function mockHttpEndpoints(array $endpoints): void
    {
        $responses = [];
        foreach ($endpoints as $pattern => $response) {
            if (\is_array($response)) {
                $responses[$pattern] = Http::response($response['data'] ?? [], $response['status'] ?? 200);
            } else {
                $responses[$pattern] = Http::response($response, 200);
            }
        }
        Http::fake($responses);
    }

    /**
     * Mock HTTP sequence of responses.
     */
    protected function mockHttpSequence(array $responses): void
    {
        $sequence = Http::sequence();
        foreach ($responses as $response) {
            if (\is_array($response)) {
                $sequence->push($response['data'] ?? [], $response['status'] ?? 200);
            } else {
                $sequence->push($response, 200);
            }
        }
        Http::fake(['*' => $sequence]);
    }

    /**
     * Assert an HTTP request was made with specific data.
     */
    protected function assertHttpRequestMade(string $url, ?array $data = null): void
    {
        Http::assertSent(static function ($request) use ($url, $data) {
            if (! str_contains($request->url(), $url)) {
                return false;
            }

            if (null === $data) {
                return true;
            }

            foreach ($data as $key => $value) {
                if ($request->data()[$key] !== $value) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Assert no HTTP requests were made.
     */
    protected function assertNoHttpRequests(): void
    {
        Http::assertNothingSent();
    }

    /**
     * Assert exact number of HTTP requests were made.
     */
    protected function assertHttpRequestCount(int $count): void
    {
        Http::assertSentCount($count);
    }
}
