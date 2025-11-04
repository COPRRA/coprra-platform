@extends('layouts.app')

@section('title', 'لوحة الإدارة')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">🏠 لوحة الإدارة الرئيسية</h1>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            <!-- Users Card -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80">المستخدمين</p>
                        <p class="text-3xl font-bold">{{ $stats['users'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl opacity-80">👥</div>
                </div>
            </div>

            <!-- Products Card -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80">المنتجات</p>
                        <p class="text-3xl font-bold">{{ $stats['products'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📦</div>
                </div>
            </div>

            <!-- Categories Card -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80">الفئات</p>
                        <p class="text-3xl font-bold">{{ $stats['categories'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl opacity-80">📂</div>
                </div>
            </div>

            <!-- Brands Card -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80">البراندات</p>
                        <p class="text-3xl font-bold">{{ $stats['brands'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl opacity-80">🏷️</div>
                </div>
            </div>

        </div>

        <!-- Admin Panels Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- AI Control Panel -->
            <a href="{{ route('admin.ai.index') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">🤖</div>
                    <h2 class="text-xl font-bold">لوحة الذكاء الاصطناعي</h2>
                </div>
                <p class="text-gray-600 mb-4">تحكم في أدوات AI والتحليلات الذكية</p>
                <div class="text-blue-600 font-semibold">الدخول →</div>
            </a>

            <!-- AI Dashboard -->
            <a href="{{ route('admin.ai.dashboard.index') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">📊</div>
                    <h2 class="text-xl font-bold">لوحة الـ Agents</h2>
                </div>
                <p class="text-gray-600 mb-4">مراقبة Agents والأداء</p>
                <div class="text-green-600 font-semibold">الدخول →</div>
            </a>

            <!-- Telescope -->
            <a href="/telescope" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">🔭</div>
                    <h2 class="text-xl font-bold">Telescope</h2>
                </div>
                <p class="text-gray-600 mb-4">مراقبة متقدمة للنظام</p>
                <div class="text-purple-600 font-semibold">الدخول →</div>
            </a>

            <!-- Scraper Control Panel -->
            <a href="{{ route('admin.scraper.index') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">🏭</div>
                    <h2 class="text-xl font-bold">محرك استيراد المحتوى</h2>
                </div>
                <p class="text-gray-600 mb-4">استيراد المنتجات تلقائياً من Amazon</p>
                <div class="text-orange-600 font-semibold">الدخول →</div>
            </a>

            <!-- Profile & 2FA -->
            <a href="{{ url('/profile') }}" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">👤</div>
                    <h2 class="text-xl font-bold">الملف الشخصي</h2>
                </div>
                <p class="text-gray-600 mb-4">إعدادات الحساب والمصادقة الثنائية</p>
                <div class="text-indigo-600 font-semibold">الدخول →</div>
            </a>

            <!-- Users Management -->
            <a href="#" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition opacity-75">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">👥</div>
                    <h2 class="text-xl font-bold">إدارة المستخدمين</h2>
                </div>
                <p class="text-gray-600 mb-4">إضافة وتعديل المستخدمين</p>
                <div class="text-gray-400 font-semibold">قريباً</div>
            </a>

            <!-- Settings -->
            <a href="#" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition opacity-75">
                <div class="flex items-center mb-4">
                    <div class="text-4xl mr-4">⚙️</div>
                    <h2 class="text-xl font-bold">الإعدادات</h2>
                </div>
                <p class="text-gray-600 mb-4">إعدادات الموقع العامة</p>
                <div class="text-gray-400 font-semibold">قريباً</div>
            </a>

        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4">⚡ إجراءات سريعة</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ url('/') }}" class="bg-gray-100 hover:bg-gray-200 px-4 py-3 rounded text-center transition">
                    🏠 الموقع الرئيسي
                </a>
                <a href="{{ url('/products') }}" class="bg-gray-100 hover:bg-gray-200 px-4 py-3 rounded text-center transition">
                    📦 المنتجات
                </a>
                <a href="{{ route('admin.ai.index') }}" class="bg-gray-100 hover:bg-gray-200 px-4 py-3 rounded text-center transition">
                    🤖 AI Panel
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="w-full bg-red-100 hover:bg-red-200 px-4 py-3 rounded text-center transition">
                        🚪 تسجيل خروج
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
