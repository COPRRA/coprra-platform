@extends('layouts.app')

@section('title', __('My Wishlist') . ' - ' . config('app.name'))
@section('description', __('Manage the products you have saved to your wishlist'))

@section('content')
<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    {{ __('Your Wishlist') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ __('Keep track of products you love and jump back in when you are ready to buy or compare.') }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <i class="fas fa-shopping-bag mr-2"></i>{{ __('Browse Products') }}
                </a>
                <a href="{{ route('compare.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                    <i class="fas fa-balance-scale mr-2"></i>{{ __('Compare Products') }}
                </a>
            </div>
        </div>

        @if($products->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($products as $product)
                    @php
                        $alreadyCompared = in_array($product->id, $compareIds, true);
                    @endphp
                    <article class="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-lg transition overflow-hidden flex flex-col">
                        <a href="{{ route('products.show', $product->slug) }}" class="block">
                            @if($product->image ?? $product->image_url)
                                <img
                                    src="{{ $product->image ?? $product->image_url }}"
                                    alt="{{ $product->name }}"
                                    class="w-full h-56 object-cover"
                                >
                            @else
                                <div class="w-full h-56 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                    <i class="fas fa-image fa-3x text-gray-400"></i>
                                </div>
                            @endif
                        </a>

                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex-1">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                    <a href="{{ route('products.show', $product->slug) }}"
                                       class="hover:text-blue-600 dark:hover:text-blue-400 transition">
                                        {{ $product->name }}
                                    </a>
                                </h2>
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 gap-2 mb-3">
                                    @if($product->brand)
                                        <span><i class="fas fa-industry mr-1"></i>{{ $product->brand->name }}</span>
                                    @endif
                                    @if($product->category)
                                        <span><i class="fas fa-tag mr-1"></i>{{ $product->category->name }}</span>
                                    @endif
                                </div>
                                @if(! is_null($product->price))
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-4">
                                        ${{ number_format((float) $product->price, 2) }}
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 space-y-2">
                                <button type="button"
                                        class="wishlist-toggle-btn w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition border border-red-500 text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20"
                                        data-product-id="{{ $product->id }}"
                                        data-wishlisted="true"
                                        data-wishlist-label-default="{{ __('Add to Wishlist') }}"
                                        data-wishlist-label-active="{{ __('Remove from Wishlist') }}"
                                        data-wishlist-icon-default="fas fa-heart"
                                        data-wishlist-icon-active="fas fa-heart-broken"
                                        data-wishlist-class-default="border-gray-300 text-gray-900 dark:text-white hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                                        data-wishlist-class-active="border-red-500 text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20">
                                    <i class="wishlist-icon fas fa-heart-broken"></i>
                                    <span class="wishlist-label">{{ __('Remove from Wishlist') }}</span>
                                </button>

                                <a href="{{ route('products.show', $product->slug) }}"
                                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition">
                                    <i class="fas fa-shopping-cart"></i>{{ __('اشتري الآن') }}
                                </a>

                                <button
                                    type="button"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white {{ $alreadyCompared ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }} transition"
                                    data-compare-add="{{ $product->id }}"
                                    data-compare-added="{{ $alreadyCompared ? 'true' : 'false' }}"
                                    data-compare-label-default="{{ __('Add to Compare') }}"
                                    data-compare-label-added="{{ __('Added to Compare') }}"
                                    data-compare-class-default="bg-blue-600 hover:bg-blue-700"
                                    data-compare-class-added="bg-green-600 hover:bg-green-700"
                                    aria-pressed="{{ $alreadyCompared ? 'true' : 'false' }}"
                                >
                                    <i class="fas fa-balance-scale"></i>
                                    {{ $alreadyCompared ? __('Added to Compare') : __('Add to Compare') }}
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-12 text-center">
                <div class="mx-auto mb-6 w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                    <i class="fas fa-heart text-3xl text-blue-500 dark:text-blue-300"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                    {{ __('Your wishlist is empty') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('Start exploring our catalog and tap the heart icon to save products you love!') }}
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center px-6 py-3 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i>{{ __('Discover Products') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
