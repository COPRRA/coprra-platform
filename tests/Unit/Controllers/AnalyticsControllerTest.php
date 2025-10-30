<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\AnalyticsController;
use App\Models\User;
use App\Services\BehaviorAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Analytics Controller Test Suite.
 *
 * Tests the AnalyticsController functionality including:
 * - User analytics retrieval
 * - Site analytics retrieval
 * - Behavior tracking
 * - Authentication checks
 * - Error handling and exceptions
 *
 * @internal
 */
#[CoversClass(AnalyticsController::class)]
#[UsesClass(BehaviorAnalysisService::class)]
#[UsesClass(User::class)]
#[CoversMethod(AnalyticsController::class, 'userAnalytics')]
#[CoversMethod(AnalyticsController::class, 'siteAnalytics')]
#[CoversMethod(AnalyticsController::class, 'trackBehavior')]
final class AnalyticsControllerTest extends TestCase
{
    private AnalyticsController $controller;
    private BehaviorAnalysisService|MockInterface $serviceMock;
    private User $user;
    private MockInterface|Request $requestMock;

    /**
     * Set up test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create service mock with proper type annotation
        $this->serviceMock = \Mockery::mock(BehaviorAnalysisService::class);
        $this->controller = new AnalyticsController($this->serviceMock);

        // Create a real User instance for testing (no database needed for unit tests)
        $this->user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->user->id = 1; // Set ID explicitly for testing

        // Create request mock with proper type annotation
        $this->requestMock = \Mockery::mock(Request::class);
    }

    /**
     * Clean up test environment after each test.
     */
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that userAnalytics returns analytics for authenticated user.
     */
    public function testUserAnalyticsReturnsAnalyticsForAuthenticatedUser(): void
    {
        // Arrange
        $analyticsData = ['key' => 'value'];

        $this->serviceMock->shouldReceive('getUserAnalytics')
            ->with($this->user)
            ->once()
            ->andReturn($analyticsData)
        ;

        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user)
        ;

        // Act
        $response = $this->controller->userAnalytics($this->requestMock);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['analytics' => $analyticsData], $response->getData(true));
    }

    /**
     * Test that userAnalytics returns unauthorized for unauthenticated user.
     */
    public function testUserAnalyticsReturnsUnauthorizedForUnauthenticatedUser(): void
    {
        // Arrange
        $this->requestMock->shouldReceive('user')
            ->andReturn(null)
        ;

        // Act
        $response = $this->controller->userAnalytics($this->requestMock);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => 'Unauthorized'], $response->getData(true));
    }

    /**
     * Test that siteAnalytics returns site analytics data.
     */
    public function testSiteAnalyticsReturnsSiteAnalytics(): void
    {
        // Arrange
        $analyticsData = ['site_key' => 'site_value'];

        $this->serviceMock->shouldReceive('getSiteAnalytics')
            ->once()
            ->andReturn($analyticsData)
        ;

        // Act
        $response = $this->controller->siteAnalytics();

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['analytics' => $analyticsData], $response->getData(true));
    }

    /**
     * Test that trackBehavior returns success for valid authenticated request.
     */
    public function testTrackBehaviorReturnsSuccessForValidAuthenticatedRequest(): void
    {
        // Arrange
        $action = 'test_action';
        $data = ['key' => 'value'];
        $validated = ['action' => $action, 'data' => $data];

        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andReturn($validated)
        ;

        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user)
        ;

        $this->serviceMock->shouldReceive('trackUserBehavior')
            ->with($this->user, $action, $data)
            ->once()
        ;

        // Act
        $response = $this->controller->trackBehavior($this->requestMock);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'message' => 'تم تسجيل السلوك بنجاح',
        ], $response->getData(true));
    }

    /**
     * Test that trackBehavior returns unauthorized for unauthenticated user.
     */
    public function testTrackBehaviorReturnsUnauthorizedForUnauthenticatedUser(): void
    {
        // Arrange
        $validated = ['action' => 'test', 'data' => []];

        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andReturn($validated)
        ;

        $this->requestMock->shouldReceive('user')
            ->andReturn(null)
        ;

        // Act
        $response = $this->controller->trackBehavior($this->requestMock);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => 'Unauthorized'], $response->getData(true));
    }

    /**
     * Test that trackBehavior fails validation for invalid action.
     */
    public function testTrackBehaviorFailsValidationForInvalidAction(): void
    {
        // Arrange
        $validator = Validator::make([], ['action' => 'required']);

        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andThrow(new ValidationException($validator))
        ;

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->controller->trackBehavior($this->requestMock);
    }

    /**
     * Test that userAnalytics throws exception when service fails.
     */
    public function testUserAnalyticsThrowsExceptionWhenServiceFails(): void
    {
        // Arrange
        $this->serviceMock->shouldReceive('getUserAnalytics')
            ->with($this->user)
            ->once()
            ->andThrow(new \Exception('Service error'))
        ;

        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user)
        ;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->controller->userAnalytics($this->requestMock);
    }

    /**
     * Test that siteAnalytics throws exception when service fails.
     */
    public function testSiteAnalyticsThrowsExceptionWhenServiceFails(): void
    {
        // Arrange
        $this->serviceMock->shouldReceive('getSiteAnalytics')
            ->once()
            ->andThrow(new \Exception('Service error'))
        ;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->controller->siteAnalytics();
    }

    /**
     * Test that trackBehavior throws exception when service fails.
     */
    public function testTrackBehaviorThrowsExceptionWhenServiceFails(): void
    {
        // Arrange
        $action = 'test_action';
        $data = ['key' => 'value'];
        $validated = ['action' => $action, 'data' => $data];

        $this->requestMock->shouldReceive('validate')
            ->with([
                'action' => 'required|string|max:50',
                'data' => 'nullable|array',
            ])
            ->andReturn($validated)
        ;

        $this->requestMock->shouldReceive('user')
            ->andReturn($this->user)
        ;

        $this->serviceMock->shouldReceive('trackUserBehavior')
            ->with($this->user, $action, $data)
            ->once()
            ->andThrow(new \Exception('Service error'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->controller->trackBehavior($this->requestMock);
    }
}
