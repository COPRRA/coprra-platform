{{-- Consolidated Internationalization Dropdown with Globe Icon --}}
<div x-data="{ i18nOpen: false }" class="relative">
    <button
        @click="i18nOpen = !i18nOpen"
        @click.away="i18nOpen = false"
        class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out"
        aria-label="Language, Country, and Currency Settings">
        <i class="fas fa-globe text-lg"></i>
    </button>

    <div
        x-show="i18nOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50 border border-gray-200 dark:border-gray-700"
        style="display: none;">

        <!-- Language Selection -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                <i class="fas fa-language mr-1"></i> {{ __('Language') }}
            </label>
            <form method="POST" action="{{ route('locale.language') }}">
                @csrf
                <select name="language" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($navLanguages as $language)
                        <option value="{{ $language->code }}" {{ $language->is_current ? 'selected' : '' }}>
                            {{ $language->native_name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- Country Selection -->
        @if(is_array($navCountries) && count($navCountries) > 0)
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                <i class="fas fa-flag mr-1"></i> {{ __('Country') }}
            </label>
            <form method="POST" action="{{ route('locale.country') }}">
                @csrf
                <select name="country" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($navCountries as $country)
                        <option value="{{ $country->code }}" {{ $country->is_current ? 'selected' : '' }}>
                            {{ $country->flag ?? '' }} {{ $country->native_name ?? $country->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        @endif

        <!-- Currency Selection -->
        <div class="px-4 py-3">
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                <i class="fas fa-dollar-sign mr-1"></i> {{ __('Currency') }}
            </label>
            <form method="POST" action="{{ route('locale.currency') }}">
                @csrf
                <select name="currency" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($navCurrencies as $currency)
                        <option value="{{ $currency->code }}" {{ $currency->is_current ? 'selected' : '' }}>
                            {{ $currency->symbol }} {{ $currency->code }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
</div>
