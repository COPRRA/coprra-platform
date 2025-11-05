<nav class="bg-white dark:bg-gray-800 shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ config('app.name', 'COPRRA') }}
                </a>
            </div>

            <!-- Centered Navigation Links -->
            <div class="hidden md:flex space-x-8 absolute left-1/2 transform -translate-x-1/2">
                <a href="{{ route('home') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out {{ request()->routeIs('home') ? 'text-blue-600 dark:text-blue-400 font-semibold' : '' }}">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="{{ route('categories.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out {{ request()->routeIs('categories.*') ? 'text-blue-600 dark:text-blue-400 font-semibold' : '' }}">
                    <i class="fas fa-tags mr-2"></i> Categories
                </a>
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out {{ request()->routeIs('products.*') ? 'text-blue-600 dark:text-blue-400 font-semibold' : '' }}">
                    <i class="fas fa-box mr-2"></i> Products
                </a>
                <a href="{{ route('brands.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out {{ request()->routeIs('brands.*') ? 'text-blue-600 dark:text-blue-400 font-semibold' : '' }}">
                    <i class="fas fa-building mr-2"></i> Brands
                </a>
            </div>

            <!-- Right Side Of Navbar -->
            <div class="hidden md:flex items-center space-x-4">
                <!-- Language Dropdown -->
                @php($navLanguages = $navLanguages ?? [])
                <form method="POST" action="{{ route('locale.language') }}" class="inline-block">
                    @csrf
                    <select name="language" onchange="this.form.submit()" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($navLanguages as $language)
                            <option value="{{ $language['code'] }}" {{ $language['is_current'] ? 'selected' : '' }}>
                                {{ $language['native_name'] }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <!-- Country Dropdown -->
                @php($navCountries = $navCountries ?? [])
                @if(is_array($navCountries) && count($navCountries) > 0)
                <form method="POST" action="{{ route('locale.country') }}" class="inline-block">
                    @csrf
                    <select name="country" onchange="this.form.submit()" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($navCountries as $country)
                            <option value="{{ $country['code'] }}" {{ $country['is_current'] ? 'selected' : '' }}>
                                {{ $country['flag'] ?? '' }} {{ $country['native_name'] ?? $country['name'] }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @endif

                <!-- Currency Dropdown -->
                @php($navCurrencies = $navCurrencies ?? [])
                <form method="POST" action="{{ route('locale.currency') }}" class="inline-block">
                    @csrf
                    <select name="currency" onchange="this.form.submit()" class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($navCurrencies as $currency)
                            <option value="{{ $currency['code'] }}" {{ $currency['is_current'] ? 'selected' : '' }}>
                                {{ $currency['symbol'] }} {{ $currency['code'] }}
                            </option>
                        @endforeach
                    </select>
                </form>

                @guest
                    <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i> Log in
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <i class="fas fa-user-plus mr-2"></i> Register
                    </a>
                @else
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <i class="fas fa-user mr-1"></i> {{ Auth::user()->name }}
                        </span>
                        <a href="{{ route('profile.edit') }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                            <i class="fas fa-cog mr-1"></i> Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-700 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400">
                                <i class="fas fa-sign-out-alt mr-1"></i> Log Out
                            </button>
                        </form>
                    </div>
                @endguest
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Main menu">
                    <i class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </div>
    </div>
</nav>
