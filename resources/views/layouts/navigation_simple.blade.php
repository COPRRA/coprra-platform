<nav class="bg-white dark:bg-gray-800 shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        <img src="{{ asset('images/logo/coprra-horizontal.svg') }}"
                             alt="COPRRA"
                             class="h-16 w-auto"
                             style="max-height: 64px;">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Home
                    </a>
                    <a href="{{ route('categories.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Categories
                    </a>
                    <a href="{{ route('products.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Products
                    </a>
                    <a href="{{ route('brands.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                        Brands
                    </a>
                </div>
            </div>

            <!-- Search Bar (Center) -->
            <div class="hidden sm:flex sm:items-center flex-1 max-w-md mx-4">
                <form action="{{ route('products.index') }}" method="GET" class="w-full">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search products..."
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                               aria-label="Search products">
                        <button type="submit" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Side Of Navbar -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @guest
                    <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Log in</a>
                    <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 dark:text-gray-500 underline">Register</a>
                @else
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ Auth::user()->name }}</span>
                            <a href="{{ route('profile.edit') }}" class="text-sm text-gray-700 dark:text-gray-500 underline">Profile</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-gray-700 dark:text-gray-500 underline">Log Out</button>
                            </form>
                        </div>
                    </div>
                @endguest
            </div>
        </div>
    </div>
</nav>
