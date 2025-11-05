@extends('layouts.app')

@section('title', 'Products - ' . config('app.name'))
@section('description', 'Browse our complete catalog of products with the best prices')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Products</h1>

            <!-- Search Form -->
            <form method="get" action="{{ route('products.search') }}" class="flex gap-2">
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search products"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                <button
                    type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                    Search
                </button>
            </form>
        </div>

        @php
            $hasQuery = filled(request('q'));
            $hasCategory = filled(request('category'));
            $hasBrand = filled(request('brand'));
            $hasActiveFilters = $hasQuery || $hasCategory || $hasBrand;
        @endphp

        @if($hasActiveFilters)
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                @if($hasQuery)
                    <div class="text-gray-700 dark:text-gray-300">
                        Showing results for: <strong>"{{ request('q') }}"</strong>
                    </div>
                @endif
                @if($hasCategory || $hasBrand)
                    <div class="mt-2 text-gray-600 dark:text-gray-400">
                        Filters:
                        @if($hasCategory)
                            <span class="inline-block bg-white dark:bg-gray-800 px-3 py-1 rounded-full text-sm">
                                Category: {{ request('category') }}
                            </span>
                        @endif
                        @if($hasBrand)
                            <span class="inline-block bg-white dark:bg-gray-800 px-3 py-1 rounded-full text-sm ml-2">
                                Brand: {{ request('brand') }}
                            </span>
                        @endif
                    </div>
                @endif
                <div class="mt-3">
                    <a href="{{ route('products.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                        Clear all filters
                    </a>
                </div>
            </div>
        @endif

        @if(isset($products) && $products->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        @if($product->image)
                            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
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
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    ${{ number_format((float)$product->price, 2) }}
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $products->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-box-open fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400 text-lg">No products found.</p>
            </div>
        @endif
    </div>
</div>
@endsection
