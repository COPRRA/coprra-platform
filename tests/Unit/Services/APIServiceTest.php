<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Api\ApiInfoService;
use App\Services\Api\PaginationService;
use App\Services\Api\RequestParameterService;
use App\Services\Api\ResponseBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class APIServiceTest extends TestCase
{
    public function testApiInfoServiceVersionAndUrls(): void
    {
        $service = new ApiInfoService();

        self::assertSame('2.0', $service->getApiVersion());
        self::assertTrue($service->checkApiVersion());

        self::assertStringEndsWith('/api/v2/documentation', $service->getApiDocumentationUrl());
        self::assertStringEndsWith('/api/v2/changelog', $service->getApiChangelogUrl());
        self::assertStringEndsWith('/api/v2/migration-guide', $service->getApiMigrationGuideUrl());

        $notices = $service->getApiDeprecationNotices();
        self::assertArrayHasKey('v1_endpoint', $notices);
        self::assertArrayHasKey('migration_guide', $notices);
        self::assertStringEndsWith('/api/v2/migration-guide', $notices['migration_guide']);
    }

    public function testResponseBuilderSuccessAndError(): void
    {
        $builder = new ResponseBuilderService();

        $success = $builder->successResponse(['id' => 1, 'name' => 'ok'], 'Success', 200, ['meta' => 'x']);
        self::assertSame(200, $success->status());
        $payload = $success->getData(true);
        self::assertTrue($payload['success']);
        self::assertSame('Success', $payload['message']);
        self::assertSame('2.0', $payload['version']);
        self::assertArrayHasKey('timestamp', $payload);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('meta', $payload);

        $error = $builder->errorResponse('Error', ['bad' => 'thing'], 422, ['ctx' => 'y']);
        self::assertSame(422, $error->status());
        $errPayload = $error->getData(true);
        self::assertFalse($errPayload['success']);
        self::assertSame('Error', $errPayload['message']);
        self::assertSame('2.0', $errPayload['version']);
        self::assertArrayHasKey('errors', $errPayload);
        self::assertArrayHasKey('meta', $errPayload);
    }

    public function testPaginatedResponseStructureWithCollection(): void
    {
        $builder = new ResponseBuilderService();
        $data = new Collection([['id' => 1], ['id' => 2]]);
        $resp = $builder->paginatedResponse($data, 'Success');
        self::assertSame(200, $resp->status());
        $payload = $resp->getData(true);
        self::assertTrue($payload['success']);
        self::assertArrayHasKey('pagination', $payload);
        self::assertArrayHasKey('version', $payload);
        self::assertSame('2.0', $payload['version']);
    }

    public function testRequestParameterServiceParsing(): void
    {
        $paramsService = new RequestParameterService();

        $request = Request::create('/api/v2/items', 'GET', [
            'include' => 'user,orders',
            'fields' => 'id,name',
            'sort_by' => 'name',
            'sort_order' => 'asc',
        ]);

        $include = $paramsService->getIncludeParams($request);
        $fields = $paramsService->getFieldsParams($request);
        $sorting = $paramsService->getSortingParams($request);
        $rate = $paramsService->getRateLimitInfo();

        self::assertArrayHasKey('user', $include);
        self::assertArrayHasKey('orders', $include);
        self::assertArrayHasKey('id', $fields);
        self::assertArrayHasKey('name', $fields);
        self::assertSame('name', $sorting['sort_by']);
        self::assertSame('asc', $sorting['sort_order']);
        self::assertSame('2.0', $rate['version']);
    }

    public function testPaginationServiceDefaultsForNonPaginator(): void
    {
        $service = new PaginationService();
        $pagination = $service->getPaginationData([['id' => 1], ['id' => 2]]);

        self::assertArrayHasKey('current_page', $pagination);
        self::assertArrayHasKey('links', $pagination);
        self::assertNull($pagination['links']['first']);
        self::assertNull($pagination['links']['last']);
        self::assertNull($pagination['links']['prev']);
        self::assertNull($pagination['links']['next']);
    }
}
