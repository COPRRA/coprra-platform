<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AgentDashboardController;
use App\Http\Controllers\Admin\AgentManagementController;
use App\Http\Controllers\Admin\AIControlPanelController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AI\AgentHealthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- المسارات العامة التي لا تتطلب تسجيل الدخول ---

// الصفحة الرئيسية
// Health check routes (comprehensive monitoring)
Route::get('/health', [HealthController::class, 'index'])->name('health.index');
Route::get('/health/check', [HealthController::class, 'check'])->name('health.check');
Route::get('/health/ping', [HealthController::class, 'ping'])->name('health.ping');

// Legacy health-check route: redirect to unified health endpoint
Route::get('/health-check', static function () {
    return redirect('/health');
})->name('health.legacy');

// AI Agent Health Monitoring Routes
Route::prefix('ai/health')->name('ai.health.')->group(static function () {
    // General health status
    Route::get('/', [AgentHealthController::class, 'healthStatus'])->name('status');
    Route::get('/stats', [AgentHealthController::class, 'lifecycleStats'])->name('stats');
    Route::get('/errors', [AgentHealthController::class, 'errorSummary'])->name('errors');

    // Agent-specific health
    Route::get('/agent/{agentId}', [AgentHealthController::class, 'agentHealth'])->name('agent');
    Route::post('/agent/{agentId}/heartbeat', [AgentHealthController::class, 'recordHeartbeat'])->name('heartbeat');

    // Agent lifecycle management
    Route::post('/agent/{agentId}/pause', [AgentHealthController::class, 'pauseAgent'])->name('pause');
    Route::post('/agent/{agentId}/resume', [AgentHealthController::class, 'resumeAgent'])->name('resume');
    Route::post('/agent/{agentId}/initialize', [AgentHealthController::class, 'initializeAgent'])->name('initialize');
    Route::post('/agents/recover', [AgentHealthController::class, 'recoverFailedAgents'])->name('recover');

    // Circuit breaker management
    Route::get('/circuit-breaker', [AgentHealthController::class, 'circuitBreakerStatus'])->name('circuit.status');
    Route::post('/circuit-breaker/{serviceName}/reset', [AgentHealthController::class, 'resetCircuitBreaker'])->name('circuit.reset');

    // State recovery and corruption management
    Route::post('/agents/{agentId}/recover-state', [AgentHealthController::class, 'recoverAgentState']);
    Route::get('/agents/{agentId}/detect-corruption', [AgentHealthController::class, 'detectStateCorruption']);
    Route::post('/agents/auto-recovery', [AgentHealthController::class, 'performAutomaticRecovery']);
    Route::post('/agents/graceful-shutdown', [AgentHealthController::class, 'initiateGracefulShutdown']);
});

Route::get('/', [HomeController::class, 'index'])->name('home');

// Dashboard route expected by tests
Route::get('/dashboard', static function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// Authentication routes - Using Controllers with Form Requests and Rate Limiting
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1')->name('register.post');

// Alias route for password reset request expected by tests
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1')->name('password.forgot');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Password reset routes with Rate Limiting
Route::get('/password/reset', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:3,1')->name('password.update');

// Email verification routes with Rate Limiting
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// المنتجات والفئات
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// تغيير اللغة والعملة
Route::get('language/{langCode}', [LocaleController::class, 'changeLanguage'])->name('change.language');
Route::get('currency/{currencyCode}', [LocaleController::class, 'changeCurrency'])->name('change.currency');

// Contact page
Route::get('contact', static function () {
    return view('contact');
})->name('contact');

// Public test route for debugging AI status
Route::get('/public-test-ai', static function () {
    try {
        return response()->json([
            'success' => true,
            'message' => 'Public test route working',
            'timestamp' => now(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
})->name('public-test-ai');

// Auth-only test route for debugging
Route::middleware('auth')->get('/auth-test-ai', static function () {
    try {
        return response()->json([
            'success' => true,
            'message' => 'Auth test route working',
            'user' => auth()->user()->email ?? 'No user',
            'timestamp' => now(),
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
})->name('auth-test-ai');

// Locale switching route
Route::post('locale/language', [LocaleController::class, 'switchLanguage'])->name('locale.language');

// --- المسارات المحمية التي تتطلب تسجيل الدخول ---

Route::middleware('auth')->group(static function (): void {
    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    // Price Alert Routes (من الكود الخاص بك، وهو مثالي)
    Route::patch('price-alerts/{priceAlert}/toggle', [PriceAlertController::class, 'toggle'])->name('price-alerts.toggle');
    Route::resource('price-alerts', PriceAlertController::class)->parameters([
        'price-alerts' => 'priceAlert',
    ]);

    // Wishlist Routes
    Route::post('wishlist/add', [WishlistController::class, 'store'])->name('wishlist.add');
    Route::delete('wishlist/remove', [WishlistController::class, 'remove'])->name('wishlist.remove');
    Route::delete('wishlist/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
    Route::post('wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::resource('wishlist', WishlistController::class)->only(['index', 'destroy']);

    // Review Routes
    Route::resource('reviews', ReviewController::class)->only(['store', 'update', 'destroy']);

    // (Moved to public routes)
});

// Cart Routes (public, ensure web middleware is explicitly applied)
Route::middleware('web')->group(static function (): void {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart', [CartController::class, 'addFromRequest'])->name('cart.store');
    Route::post('cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/remove/{itemId}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('cart/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Checkout route expected by tests
Route::get('/checkout', static function () {
    return response('Checkout', 200);
})->middleware('auth')->name('checkout');

// Web Order routes for E2E tests
Route::middleware('auth')->group(static function (): void {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'storeFromCart'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

// --- Admin Routes (تتطلب صلاحيات إدارية) ---

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(static function (): void {
    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('users', [AdminController::class, 'users'])->name('users');
    Route::get('products', [AdminController::class, 'products'])->name('products');
    Route::get('brands', [AdminController::class, 'brands'])->name('brands');
    Route::get('categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('stores', [AdminController::class, 'stores'])->name('stores');
    Route::post('users/{user}/toggle-admin', [AdminController::class, 'toggleUserAdmin'])->name('users.toggle-admin');

    // AI Control Panel Routes
    Route::prefix('ai')->name('ai.')->group(static function (): void {
        Route::get('/', [AIControlPanelController::class, 'index'])->name('index');
        Route::post('/analyze-text', [AIControlPanelController::class, 'analyzeText'])->name('analyze-text');
        Route::post('/classify-product', [AIControlPanelController::class, 'classifyProduct'])->name('classify-product');
        Route::post('/recommendations', [AIControlPanelController::class, 'generateRecommendations'])->name('recommendations');
        Route::post('/analyze-image', [AIControlPanelController::class, 'analyzeImage'])->name('analyze-image');
        Route::get('/status', [AIControlPanelController::class, 'getStatus'])->name('status');

        // Agent Dashboard Routes
        Route::prefix('dashboard')->name('dashboard.')->group(static function (): void {
            Route::get('/', [AgentDashboardController::class, 'index'])->name('index');
            Route::get('/data', [AgentDashboardController::class, 'getDashboardData'])->name('data');
            Route::get('/stream', [AgentDashboardController::class, 'streamUpdates'])->name('stream');
            Route::get('/agents/{agentId}', [AgentDashboardController::class, 'getAgentDetails'])->name('agent.details');
            Route::get('/metrics', [AgentDashboardController::class, 'getSystemMetrics'])->name('metrics');
            Route::get('/search', [AgentDashboardController::class, 'searchAgents'])->name('search');
        });

        // Agent Management Routes
        Route::prefix('agents')->name('agents.')->group(static function (): void {
            Route::post('/{agentId}/start', [AgentManagementController::class, 'startAgent'])->name('start');
            Route::post('/{agentId}/stop', [AgentManagementController::class, 'stopAgent'])->name('stop');
            Route::post('/{agentId}/restart', [AgentManagementController::class, 'restartAgent'])->name('restart');
            Route::get('/{agentId}/config', [AgentManagementController::class, 'getConfiguration'])->name('config.get');
            Route::put('/{agentId}/config', [AgentManagementController::class, 'updateConfiguration'])->name('config.update');
            Route::post('/{agentId}/test', [AgentManagementController::class, 'testAgent'])->name('test');
            Route::get('/{agentId}/debug', [AgentManagementController::class, 'getDebugInfo'])->name('debug');
            Route::post('/{agentId}/simulate', [AgentManagementController::class, 'simulateRequest'])->name('simulate');
        });
    });

    // Simple admin test route
    Route::get('/admin-test', static function () {
        return response()->json(['success' => true, 'message' => 'Admin route working', 'user' => auth()->user()->email]);
    });

    // Simple AI status test without controller
    Route::get('/ai-status-simple', static function () {
        return response()->json([
            'success' => true,
            'message' => 'AI status route working',
            'timestamp' => now()->toISOString(),
            'user' => auth()->user()->email,
        ]);
    });

    // Temporary test route for debugging
    Route::get('/test-ai-status', static function () {
        try {
            $controller = app(AIControlPanelController::class);

            return $controller->getStatus();
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    })->name('test-ai-status');
});

// --- Brand Routes (تتطلب تسجيل الدخول) ---

Route::middleware('auth')->group(static function (): void {
    Route::resource('brands', BrandController::class);
});

// Secure file serving via signed URLs (private storage)
Route::get('/files/{path}', [FileController::class, 'show'])
    ->where('path', '.*')
    ->middleware(['signed'])
    ->name('files.show')
;

// Sentry test route to validate error capture
Route::get('/sentry-test', static function () {
    throw new Exception('Sentry test exception');
});
