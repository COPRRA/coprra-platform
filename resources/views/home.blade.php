@extends('layouts.app')

@section('title', __('Home') . ' - ' . config('app.name'))
@section('description', __('main.coprra_description'))

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ __('Welcome to') }} {{ config('app.name', 'COPRRA') }}
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                {{ __('main.coprra_description') }}
            </p>
        </div>

        <!-- Featured Products -->
        @if(!empty($featuredProducts) && $featuredProducts->isNotEmpty())
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Featured Products') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredProducts as $product)
                    <article class="product-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        @php
                            $imageUrl = $product->image ?? $product->image_url ?? null;
                        @endphp
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="w-full h-48 object-cover" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23e5e7eb\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%239ca3af\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'; this.classList.add('bg-gray-200');">
                        @else
                            <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center" aria-hidden="true">
                                <i class="fas fa-image fa-3x text-gray-400"></i>
                            </div>
                        @endif

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                <a href="{{ route('products.show', $product->slug ?? $product->id) }}" class="hover:text-blue-600 dark:hover:text-blue-400" aria-label="{{ __('messages.view_details_for') }} {{ $product->name }}">
                                    {{ $product->name }}
                                </a>
                            </h3>
                            @if($product->brand)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $product->brand->name }}</p>
                            @endif
                            @if(!is_null($product->price))
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-3" aria-label="{{ __('messages.price') }}: ${{ number_format((float)$product->price, 2) }}">
                                    ${{ number_format((float)$product->price, 2) }}
                                </div>
                            @endif
                            <a href="{{ route('products.show', $product->slug ?? $product->id) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded transition" aria-label="{{ __('messages.view_details_for') }} {{ $product->name }}">
                                {{ __('View Details') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
        <!-- Loading Skeletons for Featured Products -->
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Featured Products') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @for($i = 0; $i < 4; $i++)
                    <article class="skeleton-product-card skeleton bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="skeleton-image skeleton"></div>
                        <div class="p-4">
                            <div class="skeleton-text medium skeleton mb-2"></div>
                            <div class="skeleton-text short skeleton mb-3"></div>
                            <div class="skeleton-button skeleton"></div>
                        </div>
                    </article>
                @endfor
            </div>
        </div>
        @endif

        <!-- Categories -->
        @if(!empty($categories) && $categories->isNotEmpty())
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ __('main.categories') }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($categories as $category)
                    @php
                        $catName = is_array($category) ? ($category['name'] ?? '') : ($category->name ?? '');
                        $catCount = is_array($category) ? ($category['products_count'] ?? 0) : ($category->products_count ?? 0);
                        $catSlug = is_array($category) ? ($category['slug'] ?? '') : ($category->slug ?? '');
                        $catId = is_array($category) ? ($category['id'] ?? '') : ($category->id ?? '');
                    @endphp
                    <a href="{{ $catSlug ? route('categories.show', $catSlug) : route('categories.show', $catId) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover:shadow-lg transition text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-tags text-3xl text-blue-600 dark:text-blue-400 mb-2"></i>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $catName }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $catCount }} {{ __('products') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Brands -->
        @if(!empty($brands) && $brands->isNotEmpty())
        <div class="mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ __('main.brands') }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($brands as $brand)
                    @php
                        $brandName = is_array($brand) ? ($brand['name'] ?? '') : ($brand->name ?? '');
                        $brandCount = is_array($brand) ? ($brand['products_count'] ?? 0) : ($brand->products_count ?? 0);
                        $brandSlug = is_array($brand) ? ($brand['slug'] ?? '') : ($brand->slug ?? '');
                        $brandId = is_array($brand) ? ($brand['id'] ?? '') : ($brand->id ?? '');
                    @endphp
                    <a href="{{ $brandSlug ? route('brands.show', $brandSlug) : route('brands.show', $brandId) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover:shadow-lg transition text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-building text-3xl text-blue-600 dark:text-blue-400 mb-2"></i>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $brandName }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $brandCount }} {{ __('products') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Call to Action -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-8 text-center text-white">
            <h2 class="text-3xl font-bold mb-4">{{ __('messages.start_comparing_prices') }}</h2>
            <p class="text-lg mb-6">{{ __('messages.find_best_deals_description') }}</p>
            <a href="{{ route('products.index') }}" class="inline-block bg-white text-blue-600 font-semibold py-3 px-8 rounded-lg hover:bg-gray-100 transition" aria-label="{{ __('messages.browse_all_products') }}">
                {{ __('messages.browse_all_products') }}
            </a>
        </div>
    </div>
</div>
@endsection
