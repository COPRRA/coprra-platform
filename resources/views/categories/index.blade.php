@extends('layouts.app')

@section('title', 'Categories - ' . config('app.name'))
@section('description', 'Browse all product categories')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Categories</h1>

        @if(isset($categories) && $categories->count())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($categories as $category)
                    <article class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition">
                        @if($category->image_url)
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-16 h-16 object-cover rounded-lg mb-4">
                        @else
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center mb-4">
                                <i class="fas fa-tag fa-2x text-gray-400"></i>
                            </div>
                        @endif

                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            <a href="{{ route('categories.show', $category->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $category->name }}
                            </a>
                        </h2>

                        <div class="text-gray-600 dark:text-gray-400 text-sm">
                            {{ $category->products_count ?? $category->products()->count() }} products
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $categories->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-tags fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-600 dark:text-gray-400 text-lg">No categories found.</p>
            </div>
        @endif
    </div>
</div>
@endsection
