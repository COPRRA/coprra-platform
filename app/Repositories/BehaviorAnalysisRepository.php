<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Behavior Analysis Repository.
 *
 * Handles database operations for behavior analysis.
 */
class BehaviorAnalysisRepository
{
    /**
     * Insert user behavior data.
     */
    public function insertUserBehavior(array $payload): void
    {
        DB::table('user_behaviors')->insert($payload);
    }

    /**
     * Get user behaviors for a specific period.
     */
    public function getUserBehaviors(User $user, int $days = 30): Collection
    {
        return DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get()
        ;
    }

    /**
     * Get user's purchase history with products.
     */
    public function getUserPurchaseHistory(User $user, int $limit = 10): Collection
    {
        return Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get user's order items with products for preferences analysis.
     */
    public function getUserOrderItems(User $user): Collection
    {
        return OrderItem::whereHas('order', static function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })
            ->with('product')
            ->get()
        ;
    }

    /**
     * Get total users count.
     */
    public function getTotalUsersCount(): int
    {
        return User::count();
    }

    /**
     * Get active users count.
     */
    public function getActiveUsersCount(int $days = 30): int
    {
        return User::whereHas('orders', static function ($query) use ($days): void {
            $query->where('created_at', '>=', now()->subDays($days));
        })->count();
    }

    /**
     * Get total orders count.
     */
    public function getTotalOrdersCount(): int
    {
        return Order::count();
    }

    /**
     * Get total revenue.
     */
    public function getTotalRevenue(): float
    {
        return (float) Order::sum('total_amount');
    }

    /**
     * Get average order value.
     */
    public function getAverageOrderValue(): float
    {
        return (float) Order::avg('total_amount');
    }

    /**
     * Get most viewed products from behaviors.
     */
    public function getMostViewedProducts(int $limit = 10): Collection
    {
        return DB::table('user_behaviors')
            ->where('action', 'product_view')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('data', DB::raw('count(*) as views'))
            ->groupBy('data')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get top selling products.
     */
    public function getTopSellingProducts(int $limit = 10): Collection
    {
        return Product::withSum('orderItems', 'quantity')
            ->orderBy('order_items_sum_quantity', 'desc')
            ->limit($limit)
            ->get()
        ;
    }

    /**
     * Get user behaviors by action type.
     */
    public function getUserBehaviorsByAction(User $user, string $action, int $days = 30): Collection
    {
        return DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->where('created_at', '>=', now()->subDays($days))
            ->get()
        ;
    }

    /**
     * Get user behaviors grouped by hour.
     */
    public function getUserBehaviorsByHour(User $user, int $days = 30): Collection
    {
        return DB::table('user_behaviors')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('count', 'desc')
            ->get()
        ;
    }

    /**
     * Get conversion rate data.
     */
    public function getConversionRateData(int $days = 30): array
    {
        $totalVisitors = DB::table('user_behaviors')
            ->where('action', 'page_view')
            ->where('created_at', '>=', now()->subDays($days))
            ->distinct('user_id')
            ->count()
        ;

        $totalBuyers = Order::where('created_at', '>=', now()->subDays($days))
            ->distinct('user_id')
            ->count()
        ;

        return [
            'total_visitors' => $totalVisitors,
            'total_buyers' => $totalBuyers,
        ];
    }
}
