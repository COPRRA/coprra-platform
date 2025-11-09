@extends('layouts.app')

@section('title', ($category->name ?? 'Category') . ' - ' . config('app.name'))
@section('description', $category->description ?? 'Browse products in this category')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('categories.index') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline mb-6">
            <i class="fas fa-arrow-left mr-2"></i> Back to categories
        </a>

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-gray-600 dark:text-gray-400">{{ $category->description }}</p>
            @endif
        </div>

        @if(isset($products) && $products->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    @php
                        $isWishlisted = in_array($product->id, $wishlistProductIds ?? [], true);
                    @endphp
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        @if($product->image ?? $product->image_url)
                            <img src="{{ $product->image ?? $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-image fa-3x text-gray-400"></i>
                            </div>
                        @endif

                        <div class="p-4">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                <a href="{{ route('products.show', $product->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ $product->name }}
                                </a>
                            </h2>
                            @if(!is_null($product->price))
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-3">
                                    ${{ number_format((float)$product->price, 2) }}
                                </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="flex gap-2 mt-3">
                                <button
                                    type="button"
                                    class="wishlist-toggle-btn flex-1 inline-flex items-center justify-center gap-2 py-2 px-3 rounded text-sm font-medium transition border {{ $isWishlisted ? 'border-red-500 text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20' : 'border-gray-300 text-gray-900 dark:text-white hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600' }}"
                                    data-product-id="{{ $product->id }}"
                                    data-wishlisted="{{ $isWishlisted ? 'true' : 'false' }}"
                                    data-wishlist-label-default="{{ __('Wishlist') }}"
                                    data-wishlist-label-active="{{ __('Remove') }}"
                                    data-wishlist-icon-default="fas fa-heart"
                                    data-wishlist-icon-active="fas fa-heart-broken"
                                    data-wishlist-class-default="border-gray-300 text-gray-900 dark:text-white hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                                    data-wishlist-class-active="border-red-500 text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20"
                                >
                                    <i class="wishlist-icon {{ $isWishlisted ? 'fas fa-heart-broken' : 'fas fa-heart' }}"></i>
                                    <span class="wishlist-label">{{ $isWishlisted ? __('Remove') : __('Wishlist') }}</span>
                                </button>
                                @php
                                    $alreadyCompared = in_array($product->id, session()->get('compare', []), true);
                                @endphp
                                <button
                                    type="button"
                                    class="flex-1 py-2 px-3 rounded text-sm transition text-white {{ $alreadyCompared ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }}"
                                    data-compare-add="{{ $product->id }}"
                                    data-compare-added="{{ $alreadyCompared ? 'true' : 'false' }}"
                                    data-compare-label-default="{{ __('Compare') }}"
                                    data-compare-label-added="{{ __('Added to Compare') }}"
                                    data-compare-class-default="bg-blue-600 hover:bg-blue-700"
                                    data-compare-class-added="bg-green-600 hover:bg-green-700"
                                    aria-pressed="{{ $alreadyCompared ? 'true' : 'false' }}"
                                >
                                    <i class="fas fa-balance-scale mr-1"></i>
                                    {{ $alreadyCompared ? __('Added to Compare') : __('Compare') }}
                                    </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if(method_exists($products, 'links'))
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="fas fa-box-open fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400 text-lg">No products in this category yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
