@extends('layouts.app')

@if(isset($seoMeta))
    @section('title', ($seoMeta['title'] ?? $product->name) . ' - Offers - ' . config('app.name'))
    @section('description', $seoMeta['description'] ?? 'Compare prices and find the best deals for ' . $product->name)
@else
    @section('title', $product->name . ' - Offers - ' . config('app.name'))
    @section('description', 'Compare prices and find the best deals for ' . $product->name)
@endif

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav class="mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        {{ __('Home') }}
                    </a>
                </li>
                <li class="text-gray-400 dark:text-gray-600">
                    <i class="fas fa-chevron-right"></i>
                </li>
                <li>
                    <a href="{{ route('products.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        {{ __('Products') }}
                    </a>
                </li>
                <li class="text-gray-400 dark:text-gray-600">
                    <i class="fas fa-chevron-right"></i>
                </li>
                <li>
                    <a href="{{ route('products.show', $product->slug) }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                        {{ $product->name }}
                    </a>
                </li>
                <li class="text-gray-400 dark:text-gray-600">
                    <i class="fas fa-chevron-right"></i>
                </li>
                <li class="text-gray-900 dark:text-white font-medium">
                    {{ __('Offers') }}
                </li>
            </ol>
        </nav>

        {{-- Product Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="grid md:grid-cols-2 gap-8 p-8">
                {{-- Product Image --}}
                <div>
                    @if($product->image ?? $product->image_url)
                        <img src="{{ $product->image ?? $product->image_url }}" 
                             alt="{{ $product->name }}" 
                             class="w-full rounded-lg object-cover">
                    @else
                        <div class="w-full h-96 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image fa-6x text-gray-400"></i>
                        </div>
                    @endif
                </div>

                {{-- Product Info (NO Official Price) --}}
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ $product->name }}
                    </h1>

                    @if($product->brand)
                        <div class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Brand') }}:</span>
                            <span class="ml-2 text-gray-900 dark:text-white font-medium">
                                {{ $product->brand->name }}
                            </span>
                        </div>
                    @endif

                    @if($product->category)
                        <div class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Category') }}:</span>
                            <a href="{{ route('categories.show', $product->category->slug) }}"
                               class="ml-2 text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $product->category->name }}
                            </a>
                        </div>
                    @endif

                    @if(!empty($product->description))
                        <div class="prose dark:prose-invert mb-6 max-w-none">
                            <p class="text-gray-700 dark:text-gray-300">
                                {{ Str::limit($product->description, 300) }}
                            </p>
                        </div>
                    @endif

                    {{-- Specifications --}}
                    @if($product->year_of_manufacture || $product->available_colors)
                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                                {{ __('Specifications') }}
                            </h3>

                            @if($product->year_of_manufacture)
                                <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Year of Manufacture') }}:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ $product->year_of_manufacture }}
                                    </span>
                                </div>
                            @endif

                            @if($product->available_colors && is_array($product->available_colors) && count($product->available_colors) > 0)
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('Available Colors') }}:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ $product->color_list }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Back to Product Page --}}
                    <div class="mt-6">
                        <a href="{{ route('products.show', $product->slug) }}" 
                           class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline">
                            <i class="fas fa-arrow-left mr-2"></i>
                            {{ __('Back to Product Details') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Live Prices Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ __('Live Prices from Our Partners') }}
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('Showing offers for') }} <strong>{{ $country }}</strong>
                    </p>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Sorted by price (cheapest first)') }}
                </div>
            </div>

            @if(empty($offers))
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-yellow-500 mb-4"></i>
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                        {{ __('No Offers Available') }}
                    </h3>
                    <p class="text-yellow-700 dark:text-yellow-300">
                        {{ __('We couldn\'t find any offers for this product in your selected country. Please try changing your country setting or check back later.') }}
                    </p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($offers as $offer)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-lg transition-shadow duration-200 bg-white dark:bg-gray-800">
                            {{-- Store Header --}}
                            <div class="flex items-center mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                @if($offer['store_logo'])
                                    <img src="{{ $offer['store_logo'] }}" 
                                         alt="{{ $offer['store_name'] }}" 
                                         class="h-10 w-auto mr-3 object-contain">
                                @else
                                    <div class="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center mr-3">
                                        <i class="fas fa-store text-gray-400"></i>
                                    </div>
                                @endif
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $offer['store_name'] }}
                                    </h3>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="mb-4">
                                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                                    {{ __('Price') }}
                                </div>
                                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                                    {{ $offer['currency'] }} {{ number_format($offer['price'], 2) }}
                                </div>
                            </div>

                            {{-- Availability --}}
                            <div class="mb-4">
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    @if($offer['availability'] === 'In Stock')
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                    @elseif($offer['availability'] === 'Low Stock')
                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                    @else
                                        bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                    @endif">
                                    @if($offer['availability'] === 'In Stock')
                                        <i class="fas fa-check-circle mr-2"></i>
                                    @elseif($offer['availability'] === 'Low Stock')
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                    @else
                                        <i class="fas fa-times-circle mr-2"></i>
                                    @endif
                                    {{ $offer['availability'] }}
                                </div>
                            </div>

                            {{-- Last Updated --}}
                            @if(isset($offer['last_updated']))
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                    {{ __('Last updated') }}: {{ \Carbon\Carbon::parse($offer['last_updated'])->diffForHumans() }}
                                </div>
                            @endif

                            {{-- Go to Store Button --}}
                            <a href="{{ $offer['affiliate_url'] }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition duration-150 ease-in-out inline-flex items-center justify-center"
                               aria-label="{{ __('Go to') }} {{ $offer['store_name'] }}">
                                <span>{{ __('Go to Store') }}</span>
                                <i class="fas fa-external-link-alt ml-2"></i>
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- Info Message --}}
                <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mr-3 mt-1"></i>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-semibold mb-1">{{ __('About Our Affiliate Links') }}</p>
                            <p>
                                {{ __('When you click "Go to Store" and make a purchase, we may earn a commission at no additional cost to you. This helps us keep COPRRA free and continue providing price comparison services.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

