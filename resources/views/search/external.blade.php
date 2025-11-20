@extends('layouts.app')

@section('title', __('External Search Results') . ' - ' . config('app.name'))

@push('styles')
<style>
    .loading-spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="py-8" x-data="{ isLoading: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('External Search Results') }}
        </h1>

        @if(empty($query))
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 dark:text-yellow-200">
                    {{ __('Please enter a search query.') }}
                </p>
            </div>
        @elseif(isset($searchTime) && $searchTime === null)
            {{-- Loading State --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('Searching online stores... This may take a few moments.') }}
                </p>
            </div>
        @else
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <p class="text-blue-800 dark:text-blue-200">
                        {{ __('Searching for:') }} <strong>{{ $query }}</strong> {{ __('in') }} <strong>{{ $country }}</strong>
                    </p>
                    @if(isset($searchTime))
                        <span class="text-sm text-blue-600 dark:text-blue-400">
                            {{ __('Search completed in :time seconds', ['time' => $searchTime]) }}
                        </span>
                    @endif
                </div>
            </div>

            @if(isset($error))
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <p class="text-red-800 dark:text-red-200">
                            {{ $error }}
                        </p>
                    </div>
                </div>
            @endif

            @if(empty($results))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 text-center">
                    <i class="fas fa-search fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('No Results Found') }}
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('We couldn\'t find any products matching your search. Please try a different query.') }}
                    </p>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <p>{{ __('Tips for better results:') }}</p>
                        <ul class="list-disc list-inside mt-2 space-y-1">
                            <li>{{ __('Try different keywords or spelling') }}</li>
                            <li>{{ __('Use more general search terms') }}</li>
                            <li>{{ __('Check your selected country setting') }}</li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="mb-4">
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ __('Found :count results', ['count' => count($results)]) }}
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach($results as $result)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    {{-- Store Logo --}}
                                    <div class="flex-shrink-0">
                                        @if(!empty($result['store_logo_url']))
                                            <img src="{{ $result['store_logo_url'] }}" 
                                                 alt="{{ $result['store_name'] }}" 
                                                 class="w-16 h-16 object-contain rounded">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                                <i class="fas fa-store text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Product Info --}}
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                            {{ $result['name'] }}
                                        </h3>
                                        <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">{{ $result['store_name'] }}</span>
                                            <span class="flex items-center">
                                                @if($result['availability'] === 'In Stock' || $result['availability'] === 'Available')
                                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                                    {{ __('In Stock') }}
                                                @elseif($result['availability'] === 'Sold' || $result['availability'] === 'Out of Stock')
                                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                                    {{ __('Out of Stock') }}
                                                @else
                                                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                                                    {{ $result['availability'] }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Price and Action --}}
                                    <div class="flex flex-col items-end space-y-3">
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                                {{ $result['currency'] }} {{ number_format($result['price'], 2) }}
                                            </div>
                                        </div>
                                        <a href="{{ $result['url'] }}" 
                                           target="_blank" 
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                                            <i class="fas fa-external-link-alt mr-2"></i>
                                            {{ __('Go to Store') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

