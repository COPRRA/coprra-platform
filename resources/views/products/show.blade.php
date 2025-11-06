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
                    @if($product->image)
                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full rounded-lg">
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

                    <!-- Call to Action Buttons -->
                    <div class="mt-8 flex gap-4">
                        <a href="#" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition">
                            <i class="fas fa-shopping-cart mr-2"></i>View Stores
                        </a>
                        <button class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-semibold py-3 px-6 rounded-lg transition">
                            <i class="fas fa-heart mr-2"></i>Wishlist
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
                            @if($rp->image)
                                <img src="{{ $rp->image }}" alt="{{ $rp->name }}" class="w-full h-48 object-cover">
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
