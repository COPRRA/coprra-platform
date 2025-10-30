<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CDN\Providers\CloudflareProvider;
use App\Services\CDN\Providers\S3Provider;
use App\Services\CDN\Services\CDNProviderFactory;
use App\Services\CDNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class CDNIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CDNService $cdnService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test CDN configuration
        Config::set('cdn.provider', 'cloudflare');
        Config::set('cdn.providers.cloudflare', [
            'api_token' => 'test-token',
            'account_id' => 'test-account',
            'zone_id' => 'test-zone',
            'base_url' => 'https://test-cdn.example.com',
        ]);
        Config::set('cdn.providers.aws', [
            'url' => 'https://test-s3.amazonaws.com',
            'region' => 'us-east-1',
            'access_key' => 'test-key',
            'secret_key' => 'test-secret',
        ]);

        $this->cdnService = new CDNService();
    }

    public function testCdnServiceInitializesWithCorrectProvider(): void
    {
        self::assertInstanceOf(CDNService::class, $this->cdnService);

        // Test provider factory creates correct provider
        $provider = CDNProviderFactory::create('cloudflare', [
            'api_token' => 'test-token',
            'account_id' => 'test-account',
            'zone_id' => 'test-zone',
        ]);

        self::assertInstanceOf(CloudflareProvider::class, $provider);
    }

    public function testCdnFileUploadSuccess(): void
    {
        // Mock successful Cloudflare API response
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'test-image-id',
                    'variants' => ['https://test-cdn.example.com/test-image.jpg'],
                ],
            ], 200),
        ]);

        // Create test file
        Storage::fake('public');
        $testFile = UploadedFile::fake()->image('test.jpg', 100, 100);
        Storage::disk('public')->put('test.jpg', $testFile->getContent());

        $result = $this->cdnService->uploadFile('test.jpg', 'uploads/test.jpg');

        self::assertIsArray($result);
        self::assertArrayHasKey('url', $result);
        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('provider', $result);
        self::assertSame('cloudflare', $result['provider']);
        self::assertSame('test-image-id', $result['id']);
        $this->assertStringContains('test-cdn.example.com', $result['url']);
    }

    public function testCdnFileUploadFailureHandling(): void
    {
        // Mock failed Cloudflare API response
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => false,
                'errors' => ['Upload failed'],
            ], 400),
        ]);

        Storage::fake('public');
        $testFile = UploadedFile::fake()->image('test.jpg');
        Storage::disk('public')->put('test.jpg', $testFile->getContent());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cloudflare upload failed');

        $this->cdnService->uploadFile('test.jpg');
    }

    public function testCdnMultipleFileUpload(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'test-image-id',
                    'variants' => ['https://test-cdn.example.com/test-image.jpg'],
                ],
            ], 200),
        ]);

        Storage::fake('public');
        $files = ['image1.jpg', 'image2.jpg', 'image3.jpg'];

        foreach ($files as $file) {
            $testFile = UploadedFile::fake()->image($file);
            Storage::disk('public')->put($file, $testFile->getContent());
        }

        $uploadMap = [
            'image1.jpg' => 'uploads/image1.jpg',
            'image2.jpg' => 'uploads/image2.jpg',
            'image3.jpg' => 'uploads/image3.jpg',
        ];

        $results = $this->cdnService->uploadMultipleFiles($uploadMap);

        self::assertCount(3, $results);

        foreach ($uploadMap as $localPath => $remotePath) {
            self::assertArrayHasKey($localPath, $results);
            self::assertArrayHasKey('url', $results[$localPath]);
            self::assertArrayHasKey('provider', $results[$localPath]);
        }
    }

    public function testCdnFileDeletion(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $result = $this->cdnService->deleteFile('uploads/test.jpg');

        self::assertTrue($result);

        Http::assertSent(static function ($request) {
            return 'DELETE' === $request->method()
                   && str_contains($request->url(), 'api.cloudflare.com')
                   && str_contains($request->url(), 'uploads/test.jpg');
        });
    }

    public function testCdnCachePurge(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $urls = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
        ];

        $result = $this->cdnService->purgeCache($urls);

        self::assertTrue($result);

        Http::assertSent(static function ($request) use ($urls) {
            $body = json_decode($request->body(), true);

            return 'POST' === $request->method()
                   && str_contains($request->url(), 'purge_cache')
                   && $body['files'] === $urls
                   && false === $body['purge_everything'];
        });
    }

    public function testCdnCachePurgeEverything(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $result = $this->cdnService->purgeCache([]);

        self::assertTrue($result);

        Http::assertSent(static function ($request) {
            $body = json_decode($request->body(), true);

            return 'POST' === $request->method()
                   && str_contains($request->url(), 'purge_cache')
                   && true === $body['purge_everything'];
        });
    }

    public function testCdnUrlGeneration(): void
    {
        $url = $this->cdnService->getUrl('uploads/test.jpg');

        self::assertIsString($url);
        $this->assertStringContains('uploads/test.jpg', $url);
    }

    public function testCdnFileExistsCheck(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $exists = $this->cdnService->fileExists('uploads/test.jpg');

        self::assertTrue($exists);
    }

    public function testCdnFileMetadataRetrieval(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'test-id',
                    'filename' => 'test.jpg',
                    'uploaded' => '2023-01-01T00:00:00Z',
                    'meta' => [
                        'size' => 1024,
                        'mime_type' => 'image/jpeg',
                    ],
                ],
            ], 200),
        ]);

        $metadata = $this->cdnService->getFileMetadata('uploads/test.jpg');

        self::assertIsArray($metadata);
        self::assertArrayHasKey('url', $metadata);
    }

    public function testCdnStatisticsRetrieval(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [
                    'totals' => [
                        'requests' => ['all' => 1000],
                        'bandwidth' => ['all' => 5000000],
                    ],
                ],
            ], 200),
        ]);

        $stats = $this->cdnService->getStatistics();

        self::assertIsArray($stats);
        self::assertArrayHasKey('requests', $stats);
        self::assertArrayHasKey('bandwidth', $stats);
    }

    public function testCdnConnectionTest(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $connectionTest = $this->cdnService->testConnection();

        self::assertTrue($connectionTest);
    }

    public function testCdnConnectionTestFailure(): void
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response(['success' => false], 500),
        ]);

        $connectionTest = $this->cdnService->testConnection();

        self::assertFalse($connectionTest);
    }

    public function testCdnProviderFactoryCreatesS3Provider(): void
    {
        $provider = CDNProviderFactory::create('aws', [
            'url' => 'https://test-s3.amazonaws.com',
            'region' => 'us-east-1',
            'access_key' => 'test-key',
            'secret_key' => 'test-secret',
        ]);

        self::assertInstanceOf(S3Provider::class, $provider);
    }

    public function testCdnProviderFactoryThrowsExceptionForUnsupportedProvider(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported CDN provider: unsupported');

        CDNProviderFactory::create('unsupported', []);
    }

    public function testCdnHandlesMissingFileGracefully(): void
    {
        Storage::fake('public');

        $this->expectException(\Exception::class);

        $this->cdnService->uploadFile('nonexistent.jpg');
    }

    public function testCdnMultipleFileUploadWithPartialFailures(): void
    {
        Http::fake([
            'api.cloudflare.com/*/image1.jpg' => Http::response([
                'success' => true,
                'result' => ['id' => 'success-id', 'variants' => ['https://cdn.example.com/image1.jpg']],
            ], 200),
            'api.cloudflare.com/*/image2.jpg' => Http::response(['success' => false], 400),
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('image1.jpg', 'content1');
        Storage::disk('public')->put('image2.jpg', 'content2');

        $uploadMap = [
            'image1.jpg' => 'uploads/image1.jpg',
            'image2.jpg' => 'uploads/image2.jpg',
        ];

        $results = $this->cdnService->uploadMultipleFiles($uploadMap);

        self::assertCount(2, $results);
        self::assertArrayHasKey('url', $results['image1.jpg']);
        self::assertArrayHasKey('error', $results['image2.jpg']);
        self::assertFalse($results['image2.jpg']['success']);
    }

    public function testCdnConfigurationValidation(): void
    {
        // Test with missing configuration
        Config::set('cdn.providers.cloudflare', []);

        $service = new CDNService();

        // Should not throw exception but handle gracefully
        self::assertInstanceOf(CDNService::class, $service);
    }

    public function testComprehensiveCdnWorkflow(): void
    {
        // Mock all CDN operations
        Http::fake([
            'api.cloudflare.com/*/upload*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'workflow-test-id',
                    'variants' => ['https://cdn.example.com/workflow-test.jpg'],
                ],
            ], 200),
            'api.cloudflare.com/*/workflow-test.jpg' => Http::response(['success' => true], 200),
            'api.cloudflare.com/*/purge_cache' => Http::response(['success' => true], 200),
            'api.cloudflare.com/*/analytics*' => Http::response([
                'success' => true,
                'result' => ['totals' => ['requests' => ['all' => 100]]],
            ], 200),
        ]);

        Storage::fake('public');
        $testFile = UploadedFile::fake()->image('workflow-test.jpg');
        Storage::disk('public')->put('workflow-test.jpg', $testFile->getContent());

        // 1. Test connection
        $connectionTest = $this->cdnService->testConnection();
        self::assertTrue($connectionTest);

        // 2. Upload file
        $uploadResult = $this->cdnService->uploadFile('workflow-test.jpg', 'uploads/workflow-test.jpg');
        self::assertArrayHasKey('url', $uploadResult);
        self::assertSame('workflow-test-id', $uploadResult['id']);

        // 3. Check if file exists
        $exists = $this->cdnService->fileExists('uploads/workflow-test.jpg');
        self::assertTrue($exists);

        // 4. Get file URL
        $url = $this->cdnService->getUrl('uploads/workflow-test.jpg');
        $this->assertStringContains('workflow-test.jpg', $url);

        // 5. Purge cache
        $purgeResult = $this->cdnService->purgeCache([$uploadResult['url']]);
        self::assertTrue($purgeResult);

        // 6. Get statistics
        $stats = $this->cdnService->getStatistics();
        self::assertIsArray($stats);

        // 7. Delete file
        $deleteResult = $this->cdnService->deleteFile('uploads/workflow-test.jpg');
        self::assertTrue($deleteResult);
    }
}
