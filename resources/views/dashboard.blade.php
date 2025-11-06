@extends('layouts.app')

@section('title', __('Dashboard') . ' - ' . config('app.name'))

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                {{ __('Welcome') }}, {{ Auth::user()->name }}!
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                {{ __('Welcome to your dashboard') }}
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="{{ route('products.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-6 transition">
                <div class="flex items-center">
                    <i class="fas fa-box fa-2x mr-4"></i>
                    <div>
                        <h3 class="text-xl font-semibold">{{ __('Browse Products') }}</h3>
                        <p class="text-sm opacity-90">{{ __('Explore our product catalog') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('categories.index') }}" class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-6 transition">
                <div class="flex items-center">
                    <i class="fas fa-tags fa-2x mr-4"></i>
                    <div>
                        <h3 class="text-xl font-semibold">{{ __('Categories') }}</h3>
                        <p class="text-sm opacity-90">{{ __('Browse by category') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('brands.index') }}" class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-6 transition">
                <div class="flex items-center">
                    <i class="fas fa-building fa-2x mr-4"></i>
                    <div>
                        <h3 class="text-xl font-semibold">{{ __('Brands') }}</h3>
                        <p class="text-sm opacity-90">{{ __('Shop by brand') }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- User Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Account Information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Name') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ Auth::user()->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Email') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ Auth::user()->email }}</p>
                </div>
            </div>
            <div class="mt-6">
                <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    <i class="fas fa-cog mr-2"></i> {{ __('Edit Profile') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
