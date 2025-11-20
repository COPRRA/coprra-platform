<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(isset($seoMeta))
        @php
            $defaultDescription = View::hasSection('description') ? View::yieldContent('description') : __('messages.coprra_description');
            $defaultKeywords = View::hasSection('keywords') ? View::yieldContent('keywords') : 'price comparison, shopping, deals, discounts, COPRRA';
            $defaultOgTitle = View::hasSection('og_title') ? View::yieldContent('og_title') : (View::hasSection('title') ? View::yieldContent('title') : config('app.name', 'COPRRA'));
            $defaultOgDescription = View::hasSection('og_description') ? View::yieldContent('og_description') : __('messages.coprra_description');
            $defaultOgImage = View::hasSection('og_image') ? View::yieldContent('og_image') : asset('images/logo/coprra-logo.svg');
            $defaultTitle = View::hasSection('title') ? View::yieldContent('title') : config('app.name', 'COPRRA');
        @endphp
        <meta name="description" content="{{ $seoMeta['description'] ?? $defaultDescription }}">
        <meta name="keywords" content="{{ $seoMeta['keywords'] ?? $defaultKeywords }}">
        <meta name="robots" content="{{ $seoMeta['robots'] ?? 'index, follow' }}">
        <link rel="canonical" href="{{ $seoMeta['canonical'] ?? url()->current() }}">

        <!-- Open Graph -->
        <meta property="og:title" content="{{ $seoMeta['og_title'] ?? $defaultOgTitle }}">
        <meta property="og:description" content="{{ $seoMeta['og_description'] ?? $defaultOgDescription }}">
        <meta property="og:type" content="{{ $seoMeta['og_type'] ?? 'website' }}">
        <meta property="og:url" content="{{ $seoMeta['og_url'] ?? url()->current() }}">
        <meta property="og:image" content="{{ $seoMeta['og_image'] ?? $defaultOgImage }}">

        <title>{{ $seoMeta['title'] ?? $defaultTitle }}</title>
    @else
        <meta name="description" content="@yield('description', __('messages.coprra_description'))">
        <meta name="keywords" content="@yield('keywords', 'price comparison, shopping, deals, discounts, COPRRA')">
        <meta name="author" content="{{ config('app.name', 'COPRRA') }}">
        <meta name="theme-color" content="#0A1E40">
        <meta name="color-scheme" content="light dark">

        <!-- Open Graph -->
        <meta property="og:title" content="@yield('og_title', View::hasSection('title') ? View::yieldContent('title') : config('app.name', 'COPRRA'))">
        <meta property="og:description" content="@yield('og_description', __('messages.coprra_description'))">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="@yield('og_image', asset('images/logo/coprra-logo.svg'))">

        <title>@yield('title', config('app.name', 'COPRRA'))</title>
    @endif

    <meta name="author" content="{{ config('app.name', 'COPRRA') }}">
    <meta name="theme-color" content="#0A1E40">
    <meta name="color-scheme" content="light dark">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/coprra-icon.svg') }}">
    <link rel="alternate icon" type="image/png" href="{{ asset('favicon.png') }}">

    @if(isset($productSchema))
    <!-- Product Schema (JSON-LD) -->
    <script type="application/ld+json" nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
    @json($productSchema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
    </script>
    @endif

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/coprra-icon.svg') }}">

    <!-- Fonts: use system stack locally (remove external CDNs) -->
    <!-- External font CDNs removed to meet Local First directive -->

    <!-- Icons: Font Awesome (local) -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    <!-- Critical CSS -->
    @if(\Illuminate\Support\Facades\File::exists(resource_path('css/critical.css')))
    <style nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">{!! \Illuminate\Support\Facades\File::get(resource_path('css/critical.css')) !!}</style>
    @endif

    <!-- Brand CSS -->
    <link rel="stylesheet" href="{{ asset('css/coprra-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/coprra-utilities.css') }}">

    <!-- RTL Support for Arabic, Hebrew, Urdu, Farsi -->
    @if(in_array(app()->getLocale(), ['ar', 'ur', 'fa', 'he']))
    <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif

    <!-- Additional CSS -->
    @stack('styles')

    <!-- Alpine.js - loaded globally for navigation component -->
    @if (file_exists(public_path('js/alpine.js')))
        <script defer src="{{ asset('js/alpine.js') }}"></script>
    @else
        <!-- Fallback to CDN if local file not found -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    @endif


    <!-- Scripts -->
    @if (app()->environment('testing'))
        {{-- Skip Vite during tests to avoid missing manifest errors --}}
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Livewire (excluded on home to avoid CSP eval warning) -->
    @unless (request()->routeIs('home'))
    @livewireStyles
    @endunless

    <!-- Google Analytics -->
    @if(config('services.google_analytics.id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
    <script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ config('services.google_analytics.id') }}');
    </script>
    @endif
</head>
<body class="font-sans antialiased"
      data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
      data-login-url="{{ route('login') }}"
      data-register-url="{{ route('register') }}">
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
        </main>

        <!-- Footer -->
        @include('layouts.footer')
    </div>

    <!-- Cookie Consent Banner -->
    @include('components.cookie-consent')

    <!-- Livewire (excluded on home) -->
    @unless (request()->routeIs('home'))
    @livewireScripts
    @endunless

    <!-- Theme Switcher Script -->
    <script src="{{ asset('js/theme-switcher.js') }}" defer></script>

    <!-- Additional JS -->
    @stack('scripts')
</body>
</html>
