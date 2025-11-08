@extends('layouts.app')

@section('title', ($product->name ?? 'Product') . ' - ' . config('app.name'))
@section('description', $product->description ?? 'View product details')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('products.index') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline mb-6">
            <i class="fas fa-arrow-left mr-2"></i> Back to products
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="grid md:grid-cols-2 gap-8 p-8">
                <!-- Product Image -->
                <div>
                    @if($product->image ?? $product->image_url)
                        <img src="{{ $product->image ?? $product->image_url }}" alt="{{ $product->name }}" class="w-full rounded-lg">
                    @else
                        <div class="w-full h-96 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image fa-6x text-gray-400"></i>
                        </div>
                    @endif
                </div>

                <!-- Product Details -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $product->name }}</h1>

                    @if(!is_null($product->price))
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-6">
                            ${{ number_format((float)$product->price, 2) }}
                        </div>
                    @endif

                    @if(!empty($product->description))
                        <div class="prose dark:prose-invert mb-6">
                            <p class="text-gray-700 dark:text-gray-300">{{ $product->description }}</p>
                        </div>
                    @endif

                    @if($product->category)
                        <div class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Category:</span>
                            <a href="{{ route('categories.show', $product->category->slug) }}"
                               class="ml-2 text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $product->category->name }}
                            </a>
                        </div>
                    @endif

                    @if($product->brand)
                        <div class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Brand:</span>
                            <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $product->brand->name }}</span>
                        </div>
                    @endif

                    <!-- Product Specifications -->
                    @if($product->year_of_manufacture || $product->available_colors)
                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Specifications</h3>

                            @if($product->year_of_manufacture)
                                <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">Year:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $product->year_of_manufacture }}</span>
                                </div>
                            @endif

                            @if($product->available_colors && is_array($product->available_colors) && count($product->available_colors) > 0)
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600 dark:text-gray-400">Colors:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $product->color_list }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Call to Action Buttons -->
                    <div class="mt-8 flex gap-4 flex-wrap">
                        <a href="#" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition">
                            <i class="fas fa-shopping-cart mr-2"></i>View Stores
                        </a>
                        <button
                            type="button"
                            class="wishlist-toggle-btn flex-1 min-w-[10rem] inline-flex items-center justify-center gap-2 font-semibold py-3 px-6 rounded-lg transition {{ ($isWishlisted ?? false) ? 'bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20' : 'bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }}"
                            data-product-id="{{ $product->id }}"
                            data-wishlisted="{{ ($isWishlisted ?? false) ? 'true' : 'false' }}"
                            data-wishlist-label-default="{{ __('Add to Wishlist') }}"
                            data-wishlist-label-active="{{ __('Remove from Wishlist') }}"
                            data-wishlist-icon-default="fas fa-heart"
                            data-wishlist-icon-active="fas fa-heart-broken"
                            data-wishlist-class-default="bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white"
                            data-wishlist-class-active="bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20"
                        >
                            <i class="wishlist-icon {{ ($isWishlisted ?? false) ? 'fas fa-heart-broken' : 'fas fa-heart' }} mr-2"></i>
                            <span class="wishlist-label">
                                {{ ($isWishlisted ?? false) ? __('Remove from Wishlist') : __('Add to Wishlist') }}
                            </span>
                        </button>
                        @php
                            $alreadyCompared = in_array($product->id, session()->get('compare', []), true);
                        @endphp
                        <button
                            type="button"
                            class="flex-1 min-w-[12rem] text-center py-3 px-6 rounded-lg font-semibold transition text-white {{ $alreadyCompared ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700' }}"
                            data-compare-add="{{ $product->id }}"
                            data-compare-added="{{ $alreadyCompared ? 'true' : 'false' }}"
                            data-compare-label-default="{{ __('Add to Compare') }}"
                            data-compare-label-added="{{ __('Added to Compare') }}"
                            data-compare-class-default="bg-indigo-600 hover:bg-indigo-700"
                            data-compare-class-added="bg-green-600 hover:bg-green-700"
                            aria-pressed="{{ $alreadyCompared ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-balance-scale mr-2"></i>
                            {{ $alreadyCompared ? __('Added to Compare') : __('Add to Compare') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($relatedProducts) && $relatedProducts->count())
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Related Products</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $rp)
                        <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                            @if($rp->image ?? $rp->image_url)
                                <img src="{{ $rp->image ?? $rp->image_url }}" alt="{{ $rp->name }}" class="w-full h-48 object-cover">
                            @else
                                <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <i class="fas fa-image fa-3x text-gray-400"></i>
                                </div>
                            @endif

                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    <a href="{{ route('products.show', $rp->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $rp->name }}
                                    </a>
                                </h3>
                                @if(!is_null($rp->price))
                                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                        ${{ number_format((float)$rp->price, 2) }}
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
