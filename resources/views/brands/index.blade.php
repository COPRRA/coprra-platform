@extends('layouts.app')

@section('title', 'Brands - ' . config('app.name'))
@section('description', 'Browse all product brands')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Brands</h1>

        @if(isset($brands) && $brands->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($brands as $brand)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition">
                        @if($brand->logo_url)
                            <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="w-full h-24 object-contain mb-4">
                        @else
                            <div class="w-full h-24 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-building fa-3x text-gray-400"></i>
                            </div>
                        @endif

                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 text-center">
                            {{ $brand->name }}
                        </h2>

                        @if($brand->description)
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">{{ $brand->description }}</p>
                        @endif

                        <div class="text-gray-600 dark:text-gray-400 text-sm text-center">
                            {{ $brand->products_count ?? $brand->products()->count() }} products
                        </div>

                        @if($brand->website_url)
                            <div class="mt-3 text-center">
                                <a href="{{ $brand->website_url }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                    <i class="fas fa-external-link-alt mr-1"></i> Visit website
                                </a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            @if(method_exists($brands, 'links'))
                <div class="mt-8">
                    {{ $brands->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="fas fa-building fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400 text-lg">No brands found.</p>
            </div>
        @endif
    </div>
</div>
@endsection
