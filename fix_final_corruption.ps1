# Fix corrupted files with 'final final' syntax errors
$files = @(
    "app\Http\Requests\ProductRequest.php",
    "app\Listeners\SendOrderStatusNotification.php",
    "app\Http\Middleware\ConvertEmptyStringsToNull.php",
    "app\Services\Security\VirusScanner.php",
    "app\Http\Middleware\ApiErrorHandler.php",
    "app\Jobs\ProcessHeavyOperation.php",
    "app\Services\Security\Headers\StandardSecurityHeaderStrategy.php",
    "app\Http\Middleware\HandlePrecognitiveRequests.php",
    "app\Http\Requests\UpdateReviewRequest.php",
    "app\Jobs\FetchDailyPriceUpdates.php",
    "app\Http\Middleware\PreventRequestsDuringMaintenance.php",
    "app\Http\Middleware\EnsureEmailIsVerified.php",
    "app\Jobs\SendPriceAlert.php",
    "app\Services\LogProcessing\ErrorStatisticsCalculator.php",
    "app\Http\Controllers\RecommendationController.php",
    "app\Http\Requests\UpdateBrandRequest.php",
    "app\Http\Requests\StoreCategoryRequest.php",
    "app\Services\Compression\GzipCompressionStrategy.php",
    "app\Services\Security\SecurityHeaderConfiguration.php",
    "app\Http\Controllers\Admin\AIControlPanelController.php",
    "app\Models\OrderItem.php",
    "app\Exceptions\Handler.php",
    "app\Http\Middleware\ThrottleRequests.php",
    "app\Http\Requests\ProductIndexRequest.php",
    "app\Http\Middleware\ValidatePostSize.php",
    "app\Services\Security\Headers\ContentSecurityPolicyHeaderStrategy.php",
    "app\Http\Middleware\AddQueuedCookiesToResponse.php",
    "app\Http\Middleware\SetLocale.php",
    "app\Http\Middleware\TrimStrings.php",
    "app\Http\Middleware\ValidateApiRequest.php",
    "app\Http\Requests\UpdateCategoryRequest.php",
    "app\Http\Middleware\InputSanitizationMiddleware.php",
    "app\Http\Kernel.php",
    "app\Services\LogProcessing\LogProcessingService.php",
    "app\Http\Requests\StorePriceAlertRequest.php",
    "app\Http\Controllers\PriceComparisonController.php",
    "app\Console\Kernel.php",
    "app\Services\Security\SecurityHeadersService.php",
    "app\Http\Controllers\Api\DocumentationController.php",
    "app\Services\ExchangeRates\RateProvider.php",
    "app\Notifications\ReviewNotification.php",
    "app\Http\Controllers\AdminController.php",
    "app\Observers\ProductObserver.php",
    "app\Http\Middleware\ShareErrorsFromSession.php",
    "app\Http\Requests\ForgotPasswordRequest.php",
    "app\Http\Requests\BanUserRequest.php",
    "app\Http\Middleware\StartSession.php",
    "app\Http\Middleware\ValidateSignature.php",
    "app\Http\Requests\UploadFileRequest.php",
    "app\Services\ProductService.php",
    "app\Http\Controllers\Api\OrderController.php",
    "app\Http\Requests\ProductSearchRequest.php",
    "app\Services\Compression\ContentTypeService.php",
    "app\Http\Middleware\AddCspNonce.php",
    "app\Models\UserPoint.php",
    "app\Services\Amazon\AmazonClient.php",
    "app\Http\Middleware\RequirePassword.php",
    "app\Models\PaymentMethod.php",
    "app\Http\Middleware\LocaleMiddleware.php",
    "app\Notifications\SystemNotification.php",
    "app\Mail\WelcomeMail.php",
    "app\Http\Controllers\Api\Admin\CategoryController.php",
    "database\seeders\BrandSeeder.php",
    "app\Services\LogProcessing\SystemHealthChecker.php",
    "app\Services\Backup\BackupValidator.php",
    "app\Http\Requests\StoreProductRequest.php",
    "app\Http\Middleware\AuthenticateWithBasicAuth.php",
    "app\Policies\ProductPolicy.php",
    "app\Notifications\OrderConfirmationNotification.php",
    "app\Services\LogProcessing\LogFileReader.php",
    "app\Services\LogProcessing\LogLineParser.php",
    "app\Providers\ViewServiceProvider.php",
    "app\Http\Middleware\ThrottleSensitiveOperations.php",
    "app\Http\Middleware\TrustProxies.php",
    "app\Http\Controllers\Auth\AuthController.php",
    "app\Http\Controllers\Api\AuthController.php",
    "app\Http\Requests\StoreBrandRequest.php",
    "app\Http\Middleware\RTLMiddleware.php",
    "database\seeders\PriceOfferSeeder.php",
    "app\Notifications\PriceDropNotification.php",
    "app\Http\Requests\UpdateUserRequest.php",
    "app\Http\Requests\BaseApiRequest.php",
    "app\Http\Middleware\Authenticate.php",
    "app\Http\Middleware\SubstituteBindings.php",
    "app\Http\Requests\UpdateProductRequest.php",
    "app\Http\Middleware\HandleCors.php",
    "app\Http\Middleware\EnsureResponseHasSession.php",
    "app\Exceptions\ProductUpdate.php",
    "app\Services\Security\Headers\StrictTransportSecurityHeaderStrategy.php",
    "app\Http\Requests\StoreReviewRequest.php",
    "app\Services\Security\Headers\SecurityHeaderStrategyFactory.php",
    "app\Http\Controllers\PointsController.php",
    "app\Http\Middleware\RedirectIfAuthenticated.php",
    "app\Http\Middleware\SetLocaleAndCurrency.php",
    "app\Http\Controllers\BrandController.php",
    "app\Http\Controllers\Api\PriceSearchController.php",
    "app\Providers\CollectionMacroServiceProvider.php",
    "app\Http\Middleware\AuthenticateSession.php",
    "app\Http\Controllers\CategoryController.php",
    "app\Http\Requests\CartRequest.php",
    "app\Services\StoreClients\StoreClientFactory.php",
    "app\Services\Compression\CompressionService.php",
    "app\Http\Requests\ChangePasswordRequest.php",
    "app\Http\Controllers\UploadController.php",
    "app\Http\Controllers\Api\Admin\BrandController.php",
    "app\Http\Middleware\AdminMiddleware.php",
    "app\Http\Middleware\SessionManagementMiddleware.php",
    "app\Http\Controllers\FileController.php",
    "app\Notifications\PriceAlertNotification.php",
    "app\Policies\UserPolicy.php",
    "app\Http\Middleware\TrustHosts.php",
    "app\Services\Compression\BrotliCompressionStrategy.php",
    "app\Http\Controllers\BackupController.php",
    "app\Services\Security\Headers\PermissionsPolicyHeaderStrategy.php",
    "app\Services\Backup\RestoreService.php",
    "app\Http\Requests\ProductUpdateRequest.php",
    "app\Http\Controllers\Auth\EmailVerificationController.php",
    "app\Http\Requests\ResetPasswordRequest.php",
    "app\Http\Controllers\ProductController.php",
    "app\Http\Middleware\EncryptCookies.php",
    "app\Http\Controllers\AnalyticsController.php",
    "database\seeders\CategorySeeder.php",
    "app\Services\NotificationService.php",
    "app\Http\Controllers\ProfileController.php",
    "app\Rules\DimensionSum.php",
    "app\DTO\AnalysisResult.php",
    "app\Providers\TelescopeServiceProvider.php",
    "app\Services\StoreClients\GenericStoreClient.php",
    "app\Http\Controllers\Api\AIController.php",
    "app\Http\Middleware\CheckPermission.php",
    "app\Services\FileCleanupService.php",
    "app\Services\Compression\CompressionResponseService.php",
    "app\Providers\CompressionServiceProvider.php",
    "app\Http\Controllers\ReportController.php",
    "app\Http\Controllers\WishlistController.php",
    "app\Services\Backup\BackupListService.php",
    "app\Services\Backup\BackupFileService.php",
    "app\Http\Requests\ProductCreateRequest.php",
    "app\Http\Middleware\OverrideHealthEndpoint.php",
    "database\seeders\DatabaseSeeder.php",
    "app\Http\Middleware\VerifyCsrfToken.php",
    "app\Models\PriceHistory.php",
    "app\Http\Controllers\LogController.php",
    "app\Http\Middleware\CompressionMiddleware.php",
    "app\Http\Controllers\LocaleController.php",
    "app\Providers\BroadcastServiceProvider.php",
    "app\Http\Middleware\SetCacheHeaders.php",
    "app\Http\Controllers\PriceAlertController.php",
    "app\Http\Controllers\HealthController.php",
    "app\Http\Controllers\UserController.php",
    "app\Http\Requests\UpdateCartRequest.php",
    "app\Providers\SecurityHeadersServiceProvider.php",
    "app\Http\Requests\LoginRequest.php",
    "app\Providers\LogProcessingServiceProvider.php",
    "app\Http\Middleware\Authorize.php",
    "app\Http\Middleware\SecurityHeadersMiddleware.php",
    "app\Models\Payment.php",
    "app\Http\Requests\RegisterRequest.php",
    "app\Notifications\ProductAddedNotification.php",
    "app\Http\Controllers\SettingController.php",
    "database\seeders\ExchangeRateSeeder.php",
    "database\seeders\LanguagesAndCurrenciesSeeder.php",
    "app\Http\Requests\UpdatePriceAlertRequest.php",
    "app\Models\Pivots\ProductStore.php",
    "app\Http\Middleware\CheckUserRole.php",
    "app\Http\Controllers\WebhookController.php",
    "app\Http\Requests\SwitchLanguageRequest.php",
    "app\Services\Validators\PriceChangeValidator.php",
    "app\Services\AuditService.php",
    "app\Http\Controllers\HomeController.php",
    "app\Http\Controllers\SystemController.php",
    "app\Http\Controllers\PaymentController.php",
    "app\Http\Controllers\ReviewController.php",
    "app\Exceptions\GlobalExceptionHandler.php"
)

$fixedCount = 0
$errorCount = 0

foreach ($file in $files) {
    try {
        if (Test-Path $file) {
            $content = Get-Content $file -Raw
            if ($content -match "^final final ") {
                $content = $content -replace "^final final ", ""
                Set-Content $file -Value $content -NoNewline
                Write-Host "Fixed: $file" -ForegroundColor Green
                $fixedCount++
            } else {
                Write-Host "No corruption found in: $file" -ForegroundColor Yellow
            }
        } else {
            Write-Host "File not found: $file" -ForegroundColor Red
            $errorCount++
        }
    } catch {
        Write-Host "Error processing $file`: $($_.Exception.Message)" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host "`nSummary:" -ForegroundColor Cyan
Write-Host "Fixed files: $fixedCount" -ForegroundColor Green
Write-Host "Errors: $errorCount" -ForegroundColor Red

# Also fix the special case in ApiInfoService.php
try {
    $apiInfoFile = "app\Services\Api\ApiInfoService.php"
    if (Test-Path $apiInfoFile) {
        $content = Get-Content $apiInfoFile -Raw
        if ($content -match "final final use") {
            $content = $content -replace "final final use", "use"
            Set-Content $apiInfoFile -Value $content -NoNewline
            Write-Host "Fixed special case: $apiInfoFile" -ForegroundColor Green
            $fixedCount++
        }
    }
} catch {
    Write-Host "Error fixing ApiInfoService.php: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nTotal fixed: $fixedCount files" -ForegroundColor Cyan