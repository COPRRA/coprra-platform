{{-- Dual Search Component: Internal Search and External Search --}}
<div x-data="{ searchMode: 'internal', internalSearchOpen: false, externalSearchOpen: false, isLoading: false }" class="flex items-center space-x-2 rtl:space-x-reverse">
    {{-- Search Mode Toggle --}}
    <div class="relative">
        <button
            @click="searchMode = searchMode === 'internal' ? 'external' : 'internal'"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out"
            :title="searchMode === 'internal' ? 'Switch to External Search' : 'Switch to Internal Search'">
            <i :class="searchMode === 'internal' ? 'fas fa-database' : 'fas fa-globe'" class="mr-1"></i>
            <span class="hidden sm:inline" x-show="searchMode === 'internal'" x-cloak>{{ __('Internal') }}</span>
            <span class="hidden sm:inline" x-show="searchMode === 'external'" x-cloak>{{ __('External') }}</span>
        </button>
    </div>

    {{-- Internal Search (with autocomplete) --}}
    <div x-show="searchMode === 'internal'" class="relative" style="display: none;">
        <form method="GET" action="{{ route('products.index') }}" class="relative">
            <input
                type="text"
                name="search"
                placeholder="{{ __('Search products...') }}"
                class="w-64 pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                autocomplete="off"
                x-on:focus="internalSearchOpen = true"
                x-on:blur="setTimeout(() => internalSearchOpen = false, 200)">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            
            {{-- Autocomplete dropdown (to be populated via JavaScript) --}}
            <div
                x-show="internalSearchOpen"
                class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg z-50 max-h-96 overflow-y-auto"
                style="display: none;"
                id="internal-search-autocomplete">
                {{-- Results will be populated here via JavaScript --}}
            </div>
        </form>
    </div>

    {{-- External Search --}}
    <div x-show="searchMode === 'external'" class="relative" style="display: none;">
        <form method="GET" action="{{ route('search.external') }}" class="relative" @submit="isLoading = true">
            <input
                type="text"
                name="q"
                placeholder="{{ __('Search online stores...') }}"
                class="w-64 pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                autocomplete="off"
                required>
            <i class="fas fa-globe absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-600 hover:text-blue-700">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

