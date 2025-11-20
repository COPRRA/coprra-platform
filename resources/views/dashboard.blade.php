@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">{{ __('Welcome to Your Dashboard') }}</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('Quick Links') }}</h2>
                <ul class="space-y-2">
                    {{-- Removed: My Points link (Task 16) --}}
                    <li>
                        <a href="{{ route('price-alerts.index') }}" class="text-blue-600 hover:text-blue-800">
                            {{ __('My Price Alerts') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('account.wishlist') }}" class="text-blue-600 hover:text-blue-800">
                            {{ __('My Wishlist') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('compare.index') }}" class="text-blue-600 hover:text-blue-800">
                            {{ __('Compare Products') }}
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Account Info -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('Account Information') }}</h2>
                <p class="text-gray-600 mb-2">
                    <strong>{{ __('Email') }}:</strong> {{ auth()->user()->email }}
                </p>
                <p class="text-gray-600 mb-2">
                    <strong>{{ __('Name') }}:</strong> {{ auth()->user()->name ?? __('Not set') }}
                </p>
                <a href="{{ route('profile.edit') }}" class="text-blue-600 hover:text-blue-800">
                    {{ __('Edit Profile') }}
                </a>
            </div>

            {{-- Removed: Points Summary section (Task 16) --}}
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">{{ __('Recent Activity') }}</h2>
            <p class="text-gray-600">{{ __('No recent activity to display.') }}</p>
        </div>
    </div>
</div>
@endsection

