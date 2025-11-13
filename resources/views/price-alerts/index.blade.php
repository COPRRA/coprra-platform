@extends('layouts.app')

@section('title', __('My Price Alerts') . ' - ' . config('app.name'))

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('My Price Alerts') }}</h1>
            <a href="{{ route('price-alerts.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                <i class="fas fa-plus mr-2" aria-hidden="true"></i>{{ __('Create Price Alert') }}
            </a>
        </div>

        @if($priceAlerts->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-12 text-center">
                <i class="fas fa-bell-slash fa-5x text-gray-300 dark:text-gray-600 mb-6" aria-hidden="true"></i>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">{{ __('No Price Alerts') }}</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('You don't have any active price alerts yet.') }}</p>
                <a href="{{ route('price-alerts.create') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <i class="fas fa-plus mr-2" aria-hidden="true"></i>{{ __('Create Your First Price Alert') }}
                </a>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Product') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Current Price') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Target Price') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Created') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($priceAlerts as $alert)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($alert->product->image_url ?? $alert->product->image)
                                                <img src="{{ $alert->product->image_url ?? $alert->product->image }}" alt="{{ $alert->product->name }}" class="h-10 w-10 rounded object-cover mr-3">
                                            @endif
                                            <div>
                                                <a href="{{ route('products.show', $alert->product->slug) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                                    {{ $alert->product->name }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            ${{ number_format((float)($alert->product->price ?? 0), 2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                            ${{ number_format((float)$alert->target_price, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($alert->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                <i class="fas fa-check-circle mr-1" aria-hidden="true"></i>{{ __('Active') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                <i class="fas fa-pause-circle mr-1" aria-hidden="true"></i>{{ __('Inactive') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $alert->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('price-alerts.toggle', $alert->id) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="{{ $alert->is_active ? __('Deactivate') : __('Activate') }}">
                                                    <i class="fas {{ $alert->is_active ? 'fa-pause' : 'fa-play' }}" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('price-alerts.edit', $alert->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="{{ __('Edit') }}">
                                                <i class="fas fa-edit" aria-hidden="true"></i>
                                            </a>
                                            <form method="POST" action="{{ route('price-alerts.destroy', $alert->id) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this price alert?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="{{ __('Delete') }}">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($priceAlerts->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $priceAlerts->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
