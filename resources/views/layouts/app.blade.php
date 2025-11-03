<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', __('messages.coprra_description'))">
    <meta name="keywords" content="@yield('keywords', 'price comparison, shopping, deals, discounts, COPRRA')">
    <meta name="author" content="{{ config('app.name', 'COPRRA') }}">
    <meta name="theme-color" content="#0A1E40">
    <meta name="color-scheme" content="light dark">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/coprra-icon.svg') }}">
    <link rel="alternate icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', View::getSection('title') ?? config('app.name', 'COPRRA'))">
    <meta property="og:description" content="@yield('og_description', __('messages.coprra_description'))">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/logo/coprra-logo.svg'))">

    <title>@yield('title', config('app.name', 'COPRRA'))</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/coprra-icon.svg') }}">

    <!-- Fonts: use system stack locally (remove external CDNs) -->
    <!-- External font CDNs removed to meet Local First directive -->

    <!-- Icons: Font Awesome (local) -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <!-- Critical CSS -->
    <style>{!! file_exists(public_path('build/manifest.json')) ? \Illuminate\Support\Facades\File::get(resource_path('css/critical.css')) : \Illuminate\Support\Facades\File::get(resource_path('css/critical.css')) !!}</style>

    <!-- Brand CSS -->
    <link rel="stylesheet" href="{{ asset('css/coprra-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/coprra-utilities.css') }}">

    <!-- Additional CSS -->
    @stack('styles')

    <!-- Alpine.js (local) - include only when a view declares 'alpine' section -->
    @hasSection('alpine')
    <script defer src="{{ asset('vendor/alpinejs/alpine.min.js') }}"></script>
    @endhasSection

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire (excluded on home to avoid CSP eval warning) -->
    @unless (request()->routeIs('home'))
    @livewireStyles
    @endunless

    <!-- Google Analytics -->
    @if(config('services.google_analytics.id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ config('services.google_analytics.id') }}');
    </script>
    @endif
</head>
<body class="font-sans antialiased">
    <!-- Skip Links for Accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <a href="#navigation" class="skip-link">Skip to navigation</a>

    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main id="main-content" role="main">
            @yield('content')

            <!-- Autoprefixer visual test -->
            <div class="autoprefixer-test-container">
                <span class="autoprefixer-test">Autoprefixer Test</span>
            </div>
        </main>

        <!-- Footer -->
        @include('layouts.footer')
    </div>

    <!-- Livewire (excluded on home) -->
    @unless (request()->routeIs('home'))
    @livewireScripts
    @endunless

    <!-- Additional JS -->
    @stack('scripts')
</body>
</html>
