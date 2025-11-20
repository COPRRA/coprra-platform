<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description', 'COPRRA Admin Dashboard')">
    <meta name="author" content="{{ config('app.name', 'COPRRA') }}">
    <meta name="theme-color" content="#3b82f6">

    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'COPRRA') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Admin Custom Styles -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fc;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #212529;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }

        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
            color: #6c757d;
        }

        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }

        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .border-left-danger {
            border-left: 0.25rem solid #e74a3b !important;
        }

        .text-gray-800 {
            color: #5a5c69 !important;
        }

        .text-gray-300 {
            color: #dddfeb !important;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fc;
        }

        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #000;
            color: #fff;
            padding: 8px;
            text-decoration: none;
            z-index: 1000;
        }

        .skip-link:focus {
            top: 6px;
        }

        @media (max-width: 767.98px) {
            .sidebar {
                top: 5rem;
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        /* RTL Support */
        [dir="rtl"] .sidebar {
            left: auto;
            right: 0;
        }

        [dir="rtl"] .main-content {
            margin-left: 0;
            margin-right: 240px;
        }

        [dir="rtl"] .navbar-toggler {
            right: auto;
            left: 1rem;
        }

        @media (max-width: 767.98px) {
            [dir="rtl"] .main-content {
                margin-right: 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Skip Links for Accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <a href="#sidebar" class="skip-link">Skip to sidebar</a>

    <!-- Top Navigation -->
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-cogs me-2"></i>
            {{ config('app.name', 'COPRRA') }} Admin
        </a>
        
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <div class="dropdown">
                    <a class="nav-link px-3 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i>
                        {{ auth()->user()->name ?? 'Admin' }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="fas fa-user-edit me-2"></i>الملف الشخصي
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('home') }}">
                            <i class="fas fa-home me-2"></i>الموقع الرئيسي
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                                <i class="fas fa-users me-2"></i>
                                المستخدمون
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                                <i class="fas fa-box me-2"></i>
                                المنتجات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                                <i class="fas fa-tags me-2"></i>
                                الفئات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.brands*') ? 'active' : '' }}" href="{{ route('admin.brands.index') }}">
                                <i class="fas fa-trademark me-2"></i>
                                العلامات التجارية
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.stores*') ? 'active' : '' }}" href="{{ route('admin.stores') }}">
                                <i class="fas fa-store me-2"></i>
                                المتاجر
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>الذكاء الاصطناعي</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.ai.index') ? 'active' : '' }}" href="{{ route('admin.ai.index') }}">
                                <i class="fas fa-brain me-2"></i>
                                لوحة التحكم AI
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.ai.dashboard*') ? 'active' : '' }}" href="{{ route('admin.ai.dashboard.index') }}">
                                <i class="fas fa-robot me-2"></i>
                                إدارة الوكلاء
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('ai.health*') ? 'active' : '' }}" href="{{ route('ai.health.status') }}">
                                <i class="fas fa-heartbeat me-2"></i>
                                صحة النظام
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>النظام</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('health.index') }}">
                                <i class="fas fa-stethoscope me-2"></i>
                                حالة النظام
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" onclick="clearCache()">
                                <i class="fas fa-broom me-2"></i>
                                مسح التخزين المؤقت
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Admin Scripts -->
    <script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
        // Clear cache function
        function clearCache() {
            if (confirm('هل أنت متأكد من أنك تريد مسح التخزين المؤقت؟')) {
                // This would typically make an AJAX call to clear cache
                alert('سيتم إضافة هذه الميزة قريباً');
            }
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

    @yield('scripts')
</body>
</html>