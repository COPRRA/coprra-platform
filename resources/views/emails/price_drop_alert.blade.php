<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Price Drop Alert') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 0;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1f2937;
        }
        .product-section {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .product-image {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .product-name {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }
        .price-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background-color: #ffffff;
            border-radius: 6px;
            border-left: 4px solid #10b981;
        }
        .price-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .price-value {
            font-size: 24px;
            font-weight: 700;
            color: #10b981;
        }
        .target-price {
            color: #3b82f6;
        }
        .cta-button {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background-color: #059669;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{{ __('Price Drop Alert') }} 🎉</h1>
        </div>

        <div class="email-body">
            <div class="greeting">
                {{ __('Hello') }}, {{ $user->name ?? $user->email }}!
            </div>

            <p>
                {{ __('Great news! The price for a product you\'re watching has dropped.') }}
            </p>

            <div class="product-section">
                @if($product->image ?? $product->image_url)
                    <img src="{{ $product->image ?? $product->image_url }}" 
                         alt="{{ $product->name }}" 
                         class="product-image">
                @endif

                <div class="product-name">
                    {{ $product->name }}
                </div>

                <div class="price-info">
                    <div>
                        <div class="price-label">{{ __('Your Target Price') }}</div>
                        <div class="price-value target-price">
                            ${{ number_format($targetPrice, 2) }}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="price-label">{{ __('New Price') }}</div>
                        <div class="price-value">
                            ${{ number_format($currentPrice, 2) }}
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ $offersUrl }}" class="cta-button">
                        {{ __('Buy Now') }} →
                    </a>
                </div>
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px;">
                {{ __('This alert has been automatically deactivated. If you\'d like to set a new price alert, visit the product page.') }}
            </p>
        </div>

        <div class="email-footer">
            <p>
                {{ __('This is an automated notification from') }} {{ config('app.name') }}.
            </p>
            <p>
                {{ __('If you have any questions, please contact our support team.') }}
            </p>
        </div>
    </div>
</body>
</html>

