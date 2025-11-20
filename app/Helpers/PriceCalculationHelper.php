<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Product;

/**
 * Price Calculation Helper.
 *
 * Provides utility methods for calculating prices, taxes, shipping, and discounts.
 */
class PriceCalculationHelper
{
    private const TAX_RATE = 0.08; // 8% tax rate
    private const FREE_SHIPPING_THRESHOLD = 50.00;
    private const STANDARD_SHIPPING_RATE = 5.99;

    /**
     * Calculate subtotal for cart items.
     *
     * @param array<int, array{product_id: int, quantity: int}> $cartItems
     */
    public static function calculateSubtotal(array $cartItems): float
    {
        $subtotal = 0.0;

        foreach ($cartItems as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if (! is_numeric($productId) || ! is_numeric($quantity)) {
                continue;
            }

            $product = Product::find((int) $productId);
            if ($product) {
                $subtotal += $product->price * (int) $quantity;
            }
        }

        return round($subtotal, 2);
    }

    /**
     * Calculate tax amount for cart items.
     *
     * @param array<int, array{product_id: int, quantity: int}> $cartItems
     */
    public static function calculateTax(array $cartItems): float
    {
        $subtotal = self::calculateSubtotal($cartItems);

        return round($subtotal * self::TAX_RATE, 2);
    }

    /**
     * Calculate shipping cost for cart items.
     *
     * @param array<int, array{product_id: int, quantity: int}> $cartItems
     */
    public static function calculateShipping(array $cartItems): float
    {
        $subtotal = self::calculateSubtotal($cartItems);

        if ($subtotal >= self::FREE_SHIPPING_THRESHOLD) {
            return 0.0;
        }

        return self::STANDARD_SHIPPING_RATE;
    }

    /**
     * Calculate total amount including subtotal, tax, and shipping.
     *
     * @param array<int, array{product_id: int, quantity: int}> $cartItems
     */
    public static function calculateTotal(array $cartItems): float
    {
        $subtotal = self::calculateSubtotal($cartItems);
        $tax = self::calculateTax($cartItems);
        $shipping = self::calculateShipping($cartItems);

        return round($subtotal + $tax + $shipping, 2);
    }

    /**
     * Calculate discount amount based on discount percentage.
     */
    public static function calculateDiscount(float $amount, float $discountPercentage): float
    {
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            return 0.0;
        }

        return round($amount * ($discountPercentage / 100), 2);
    }

    /**
     * Apply discount to an amount.
     */
    public static function applyDiscount(float $amount, float $discountPercentage): float
    {
        $discount = self::calculateDiscount($amount, $discountPercentage);

        return round($amount - $discount, 2);
    }

    /**
     * Format price for display.
     */
    public static function formatPrice(float $price, string $currency = 'USD'): string
    {
        return match ($currency) {
            'USD' => '$'.number_format($price, 2),
            'EUR' => '€'.number_format($price, 2),
            'GBP' => '£'.number_format($price, 2),
            default => $currency.' '.number_format($price, 2),
        };
    }
}
