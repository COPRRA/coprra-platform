@extends('layouts.app')

@section('title', __('main.stores') . ' - ' . config('app.name'))
@section('description', 'Discover our partner stores and find the best deals on thousands of products.')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
            <div class="max-w-2xl mx-auto">
                <i class="fas fa-store fa-5x text-blue-600 dark:text-blue-400 mb-6"></i>
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ __('main.stores') }}
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-6">
                    Coming Soon! We are working on partnerships with the best online stores to bring you the best deals.
                </p>
                <p class="text-base text-gray-500 dark:text-gray-500 mb-8">
                    Our team is actively building relationships with trusted retailers to provide you with comprehensive price comparisons and exclusive offers.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('products.index') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-shopping-bag mr-2" aria-hidden="true"></i>
                        {{ __('main.products') }}
                    </a>
                    <a href="{{ route('home') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-semibold rounded-lg transition">
                        <i class="fas fa-home mr-2" aria-hidden="true"></i>
                        {{ __('main.home') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

