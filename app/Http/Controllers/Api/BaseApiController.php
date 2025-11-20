<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;

/**
 * @OA\Info(
 *     title="COPRRA API",
 *     version="1.0.0",
 *     description="API for COPRRA - Price Comparison Platform",
 *
 *     @OA\Contact(
 *         email="api@coprra.com",
 *         name="COPRRA API Support"
 *     ),
 *
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://api.coprra.com",
 *     description="Production Server"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-Key"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization"
 * )
 * @OA\Tag(
 *     name="Products",
 *     description="Product management and search"
 * )
 * @OA\Tag(
 *     name="Categories",
 *     description="Product categories"
 * )
 * @OA\Tag(
 *     name="Brands",
 *     description="Product brands"
 * )
 * @OA\Tag(
 *     name="Stores",
 *     description="Store management"
 * )
 * @OA\Tag(
 *     name="Reviews",
 *     description="Product reviews"
 * )
 * @OA\Tag(
 *     name="Wishlist",
 *     description="User wishlist management"
 * )
 * @OA\Tag(
 *     name="Price Alerts",
 *     description="Price alert management"
 * )
 * @OA\Tag(
 *     name="Statistics",
 *     description="Platform statistics and analytics"
 * )
 * @OA\Tag(
 *     name="Reports",
 *     description="Report generation"
 * )
 */
abstract class BaseApiController extends Controller
{
    use ApiResponse;

    protected int $perPage = 15;

    protected int $maxPerPage = 100;

    // Get filtering parameters.

    // Get search parameters.

    // Get rate limit information.
}
