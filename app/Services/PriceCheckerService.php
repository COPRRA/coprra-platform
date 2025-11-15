<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\PriceDropAlert;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service for checking price alerts and sending notifications.
 */
final class PriceCheckerService
{
    /**
     * Check all active price alerts and send notifications for triggered alerts.
     *
     * @return array{
     *     total_checked: int,
     *     products_checked: int,
     *     alerts_triggered: int,
     *     notifications_sent: int,
     *     errors: int
     * }
     */
    public function checkAlerts(): array
    {
        $stats = [
            'total_checked' => 0,
            'products_checked' => 0,
            'alerts_triggered' => 0,
            'notifications_sent' => 0,
            'errors' => 0,
        ];

        try {
            // Fetch all active price alerts
            $activeAlerts = PriceAlert::active()
                ->with(['product', 'user'])
                ->get();

            $stats['total_checked'] = $activeAlerts->count();

            if ($activeAlerts->isEmpty()) {
                Log::info('No active price alerts to check');
                return $stats;
            }

            // Group alerts by product_id to avoid fetching the same product multiple times
            $alertsByProduct = $activeAlerts->groupBy('product_id');

            $stats['products_checked'] = $alertsByProduct->count();

            // Process each product's alerts
            foreach ($alertsByProduct as $productId => $alerts) {
                try {
                    $product = $alerts->first()->product;

                    if (!$product) {
                        Log::warning('Product not found for price alert', [
                            'product_id' => $productId,
                        ]);
                        $stats['errors']++;
                        continue;
                    }

                    // Get current official price
                    $currentPrice = (float) ($product->price ?? 0);

                    if ($currentPrice <= 0) {
                        Log::warning('Product has no valid price', [
                            'product_id' => $productId,
                            'product_name' => $product->name,
                        ]);
                        continue;
                    }

                    // Check each alert for this product
                    foreach ($alerts as $alert) {
                        try {
                            // Check if price target has been reached
                            if ($alert->isPriceTargetReached($currentPrice)) {
                                $stats['alerts_triggered']++;

                                // Send email notification
                                try {
                                    Mail::to($alert->user->email)->send(
                                        new PriceDropAlert($alert, $product, $currentPrice)
                                    );

                                    $stats['notifications_sent']++;

                                    Log::info('Price drop alert sent', [
                                        'alert_id' => $alert->id,
                                        'user_id' => $alert->user_id,
                                        'product_id' => $productId,
                                        'target_price' => $alert->target_price,
                                        'current_price' => $currentPrice,
                                    ]);

                                    // Deactivate the alert (unless repeat_alert is enabled)
                                    if (!$alert->repeat_alert) {
                                        $alert->update(['is_active' => false]);
                                    }
                                } catch (\Exception $e) {
                                    $stats['errors']++;
                                    Log::error('Failed to send price drop alert email', [
                                        'alert_id' => $alert->id,
                                        'user_id' => $alert->user_id,
                                        'product_id' => $productId,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            $stats['errors']++;
                            Log::error('Error processing price alert', [
                                'alert_id' => $alert->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error processing product alerts', [
                        'product_id' => $productId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Fatal error in price checker service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }
}

