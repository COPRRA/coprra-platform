@extends('layouts.app')

@if(isset($seoMeta))
    @section('title', $seoMeta['title'] ?? 'Brand - ' . config('app.name'))
    @section('description', $seoMeta['description'] ?? 'Browse brand products')
@endif

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $brand->name }}</h1>
            @if($brand->description)
                <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $brand->description }}</p>
            @endif
            @if($brand->logo_url)
                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="h-24 mb-4">
            @endif
        </div>

        @if($products->count() > 0)
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                        @if($product->image ?? $product->image_url)
                            <img src="{{ $product->image ?? $product->image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-image fa-3x text-gray-400"></i>
                            </div>
                        @endif

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                <a href="{{ route('products.show', $product->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ $product->name }}
                                </a>
                            </h3>
                            @if(!is_null($product->price))
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-3">
                                    ${{ number_format((float)$product->price, 2) }}
                                </div>
                            @endif
                            <a href="{{ route('products.show', $product->slug) }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded transition">
                                View Details
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @else
            <p class="text-gray-600 dark:text-gray-400">No products found for this brand.</p>
        @endif
    </div>
</div>
@endsection
