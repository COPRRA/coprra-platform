@extends('layouts.app')

@section('title', __('My Points') . ' - ' . config('app.name'))

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">{{ __('My Points') }}</h1>

        <!-- Points Balance Card -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold mb-2">{{ __('Available Points') }}</h2>
                    <div class="text-5xl font-bold">{{ number_format($availablePoints ?? 0) }}</div>
                </div>
                <div class="text-6xl opacity-50">
                    <i class="fas fa-coins" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Rewards Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Available Rewards') }}</h2>

                @if($rewards->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-gift fa-3x mb-4 opacity-50" aria-hidden="true"></i>
                        <p>{{ __('No rewards available at the moment.') }}</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($rewards as $reward)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ $reward->name }}</h3>
                                        @if($reward->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $reward->description }}</p>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                <i class="fas fa-coins mr-1" aria-hidden="true"></i>{{ number_format($reward->points_required) }} {{ __('points') }}
                                            </span>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('account.rewards.redeem', $reward->id) }}" class="ml-4">
                                        @csrf
                                        <button type="submit" 
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition {{ ($availablePoints ?? 0) < $reward->points_required ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                {{ ($availablePoints ?? 0) < $reward->points_required ? 'disabled' : '' }}
                                                title="{{ ($availablePoints ?? 0) < $reward->points_required ? __('Insufficient points') : __('Redeem Reward') }}">
                                            <i class="fas fa-gift mr-2" aria-hidden="true"></i>{{ __('Redeem') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Points History Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Points History') }}</h2>

                @if($pointHistory->isEmpty())
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-history fa-3x mb-4 opacity-50" aria-hidden="true"></i>
                        <p>{{ __('No points history yet.') }}</p>
                    </div>
                @else
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($pointHistory as $point)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($point->points > 0)
                                            <i class="fas fa-plus-circle text-green-500" aria-hidden="true"></i>
                                        @else
                                            <i class="fas fa-minus-circle text-red-500" aria-hidden="true"></i>
                                        @endif
                                        <span class="font-semibold {{ $point->points > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $point->points > 0 ? '+' : '' }}{{ number_format($point->points) }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $point->description ?? ucfirst($point->type) }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $point->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Manual Redemption Form -->
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Redeem Points Manually') }}</h2>
            <form method="POST" action="{{ route('account.points.redeem') }}" class="flex gap-4">
                @csrf
                <div class="flex-1">
                    <label for="points" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Points to Redeem') }}</label>
                    <input type="number" name="points" id="points" min="1" max="{{ $availablePoints ?? 0 }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div class="flex-1">
                    <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Reason') }} ({{ __('Optional') }})</label>
                    <input type="text" name="reason" id="reason" maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        <i class="fas fa-exchange-alt mr-2" aria-hidden="true"></i>{{ __('Redeem') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

