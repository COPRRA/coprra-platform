<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\OrderController;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PointsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\DatabaseSetup;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class OrderControllerTest extends TestCase
{
    use DatabaseSetup;

    private OrderController $controller;

    private MockInterface|OrderService $orderServiceMock;

    private MockInterface|PointsService $pointsServiceMock;

    private User $user;

    private Request $requestMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        // Verify method existence for better test reliability
        self::assertTrue(
            method_exists(OrderService::class, 'getOrderHistory'),
            'OrderService must have getOrderHistory method'
        );

        $this->orderServiceMock = \Mockery::mock(OrderService::class);
        $this->pointsServiceMock = \Mockery::mock(PointsService::class);
        $this->controller = \Mockery::mock(OrderController::class, [$this->orderServiceMock, $this->pointsServiceMock])->makePartial();
        $this->user = User::factory()->create();

        // Use real Request instead of mocking simple data structures
        $this->request = new Request();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testIndexReturnsOrdersForAuthenticatedUser(): void
    {
        // Arrange
        $orders = Collection::make([['id' => 1]]);

        // Set up real request with query parameters
        $this->request->merge(['limit' => 10]);
        $this->request->setUserResolver(function () {
            return $this->user;
        });

        $this->orderServiceMock->shouldReceive('getOrderHistory')
            ->with($this->user, 10)
            ->andReturn($orders)
        ;

        // Act
        $response = $this->controller->index($this->request);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        self::assertArrayHasKey('orders', $data);
        self::assertSame($orders->toArray(), $data['orders']);
    }

    public function testIndexReturnsUnauthorizedForUnauthenticatedUser(): void
    {
        // Arrange
        $this->request->setUserResolver(static function () {
            return null;
        });

        // Act
        $response = $this->controller->index($this->request);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => 'Unauthorized'], $response->getData(true));
    }

    public function testShowReturnsOrderWithLoadedRelationships(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $order->shouldReceive('load')->with(['items.product', 'payments.paymentMethod'])->andReturnSelf();
        $order->shouldReceive('jsonSerialize')->andReturn(['id' => 1]);
        $order->shouldReceive('getAttribute')->with('user_id')->andReturn($this->user->id);
        $this->requestMock->shouldReceive('user')->andReturn($this->user);

        // Mock authorize (not used in controller but kept for compatibility)
        $this->controller->shouldReceive('authorize')->with('view', $order)->byDefault();

        // Act
        $response = $this->controller->show($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['order' => ['id' => 1]], $response->getData(true));
    }

    public function testCreateCreatesOrderAndAwardsPointsForValidRequest(): void
    {
        // Arrange
        $cartItems = [['product_id' => 1, 'quantity' => 2]];
        $addresses = ['shipping' => [], 'billing' => []];
        $validated = ['cart_items' => $cartItems, 'shipping_address' => [], 'billing_address' => []];
        $order = \Mockery::mock(Order::class);
        $this->requestMock->shouldReceive('validate')
            ->with([
                'cart_items' => 'required|array',
                'cart_items.*.product_id' => 'required|exists:products,id',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'shipping_address' => 'required|array',
                'billing_address' => 'required|array',
            ])
            ->andReturn($validated)
        ;
        $this->requestMock->shouldReceive('user')->andReturn($this->user);
        $this->orderServiceMock->shouldReceive('createOrder')
            ->with($this->user, $cartItems, $addresses)
            ->andReturn($order)
        ;
        $order->shouldReceive('jsonSerialize')->andReturn(['id' => 1]);
        $this->pointsServiceMock->shouldReceive('awardPurchasePoints')
            ->with($order)
        ;

        // Act
        $response = $this->controller->create($this->requestMock);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(201, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'order' => ['id' => 1],
            'message' => 'تم إنشاء الطلب بنجاح',
        ], $response->getData(true));
    }

    public function testCreateReturnsUnauthorizedForUnauthenticatedUser(): void
    {
        // Arrange
        $validated = ['cart_items' => [], 'shipping_address' => [], 'billing_address' => []];
        $this->requestMock->shouldReceive('validate')->andReturn($validated);
        $this->requestMock->shouldReceive('user')->andReturn(null);

        // Act
        $response = $this->controller->create($this->requestMock);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['error' => 'Unauthorized'], $response->getData(true));
    }

    public function testUpdateStatusUpdatesStatusAndSendsNotification(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $user = User::factory()->create();
        $validated = ['status' => 'processing'];
        $this->requestMock->shouldReceive('validate')
            ->with(['status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded'])
            ->andReturn($validated)
        ;
        $order->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $order->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->orderServiceMock->shouldReceive('updateOrderStatus')
            ->with($order, 'processing')
            ->andReturn(true)
        ;
        $order->shouldReceive('getAttribute')->with('user')->andReturn($user);

        // Act
        $response = $this->controller->updateStatus($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
        ], $response->getData(true));
        self::assertTrue(DB::table('notifications')->where('user_id', $user->id)->exists());
    }

    public function testUpdateStatusReturnsErrorWhenUpdateFails(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $validated = ['status' => 'invalid'];
        $this->requestMock->shouldReceive('validate')->andReturn($validated);
        $order->shouldReceive('getAttribute')->with('status')->andReturn('pending');
        $this->orderServiceMock->shouldReceive('updateOrderStatus')
            ->with($order, 'invalid')
            ->andReturn(false)
        ;

        // Act
        $response = $this->controller->updateStatus($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame([
            'success' => false,
            'message' => 'لا يمكن تحديث حالة الطلب',
        ], $response->getData(true));
    }

    public function testCancelCancelsOrderSuccessfully(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $validated = ['reason' => 'Changed mind'];
        $this->requestMock->shouldReceive('validate')
            ->with(['reason' => 'nullable|string|max:500'])
            ->andReturn($validated)
        ;
        $this->orderServiceMock->shouldReceive('cancelOrder')
            ->with($order, 'Changed mind')
            ->andReturn(true)
        ;

        // Act
        $response = $this->controller->cancel($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح',
        ], $response->getData(true));
    }

    public function testCancelReturnsErrorWhenCancelFails(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $validated = ['reason' => null];
        $this->requestMock->shouldReceive('validate')->andReturn($validated);
        $this->orderServiceMock->shouldReceive('cancelOrder')
            ->with($order, null)
            ->andReturn(false)
        ;

        // Act
        $response = $this->controller->cancel($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame([
            'success' => false,
            'message' => 'لا يمكن إلغاء الطلب',
        ], $response->getData(true));
    }

    public function testShowThrowsAuthorizationExceptionWhenNotAuthorized(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $order->shouldReceive('getAttribute')->with('user_id')->andReturn(999);
        $this->requestMock->shouldReceive('user')->andReturn($this->user);

        // Act
        $response = $this->controller->show($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame(['error' => 'Unauthorized'], $response->getData(true));
    }

    public function testUpdateStatusReturnsErrorForInvalidTransition(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $validated = ['status' => 'processing'];
        $this->requestMock->shouldReceive('validate')
            ->with(['status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded'])
            ->andReturn($validated)
        ;
        $order->shouldReceive('getAttribute')->with('status')->andReturn('delivered'); // Invalid transition
        $this->orderServiceMock->shouldReceive('updateOrderStatus')
            ->with($order, 'processing')
            ->andReturn(false)
        ;

        // Act
        $response = $this->controller->updateStatus($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame([
            'success' => false,
            'message' => 'لا يمكن تحديث حالة الطلب',
        ], $response->getData(true));
    }

    public function testCancelReturnsErrorForInvalidStatus(): void
    {
        // Arrange
        $order = \Mockery::mock(Order::class);
        $validated = ['reason' => 'Changed mind'];
        $this->requestMock->shouldReceive('validate')
            ->with(['reason' => 'nullable|string|max:500'])
            ->andReturn($validated)
        ;
        $order->shouldReceive('getAttribute')->with('status')->andReturn('shipped'); // Can't cancel shipped
        $this->orderServiceMock->shouldReceive('cancelOrder')
            ->with($order, 'Changed mind')
            ->andReturn(false)
        ;

        // Act
        $response = $this->controller->cancel($this->requestMock, $order);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame([
            'success' => false,
            'message' => 'لا يمكن إلغاء الطلب',
        ], $response->getData(true));
    }
}
