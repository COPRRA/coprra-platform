{{-- Triple Dropdown Component: Language, Country, Currency --}}
<div class="flex items-center space-x-2">
    {{-- Language Dropdown --}}
    <div x-data="{ languageOpen: {{ request()->get('open') === 'language' ? 'true' : 'false' }} }" class="relative">
        <button
            @click="languageOpen = !languageOpen"
            @click.away="languageOpen = false"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out"
            aria-label="{{ __('Language') }}">
            <i class="fas fa-language mr-1"></i>
            @php
                $currentLang = collect($navLanguages)->firstWhere('is_current', true);
            @endphp
            <span class="hidden sm:inline" x-show="!languageOpen">{{ $currentLang?->native_name ?? app()->getLocale() }}</span>
        </button>
        <div
            x-show="languageOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50 border border-gray-200 dark:border-gray-700"
            style="display: none;">
            <form method="POST" action="{{ route('locale.language') }}" id="language-form" class="flex flex-col">
                @csrf
                @foreach($navLanguages as $language)
                    <label class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer block w-full">
                        <input type="radio" name="language" value="{{ $language->code }}" {{ $language->is_current ? 'checked' : '' }} onchange="document.getElementById('language-form').submit();" class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $language->native_name }}</span>
                    </label>
                @endforeach
            </form>
        </div>
    </div>

    {{-- Country Dropdown --}}
    @if(is_array($navCountries) && count($navCountries) > 0)
    <div x-data="{ countryOpen: {{ request()->get('open') === 'country' ? 'true' : 'false' }} }" class="relative">
        <button
            @click="countryOpen = !countryOpen"
            @click.away="countryOpen = false"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out"
            aria-label="{{ __('Country') }}">
            <i class="fas fa-flag mr-1"></i>
            @php
                $currentCountry = collect($navCountries)->firstWhere('is_current', true);
                $defaultCountry = collect($navCountries)->firstWhere('code', config('app.default_country', 'US'));
                $displayCountry = $currentCountry ?? $defaultCountry;
            @endphp
            <span class="hidden sm:inline" x-show="!countryOpen">{{ $displayCountry?->flag ?? '' }} {{ $displayCountry?->native_name ?? $displayCountry?->name ?? __('Select Country') }}</span>
        </button>
        <div
            x-show="countryOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50 border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto"
            style="display: none;">
            <form method="POST" action="{{ route('locale.country') }}" id="country-form" class="flex flex-col">
                @csrf
                @foreach($navCountries as $country)
                    <label class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer block w-full">
                        <input type="radio" name="country" value="{{ $country->code }}" {{ $country->is_current ? 'checked' : '' }} onchange="document.getElementById('country-form').submit();" class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $country->flag ?? '' }} {{ $country->native_name ?? $country->name }}</span>
                    </label>
                @endforeach
            </form>
        </div>
    </div>
    @endif

    {{-- Currency Dropdown --}}
    <div x-data="{ currencyOpen: {{ request()->get('open') === 'currency' ? 'true' : 'false' }} }" class="relative">
        <button
            @click="currencyOpen = !currencyOpen"
            @click.away="currencyOpen = false"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md transition duration-150 ease-in-out"
            aria-label="{{ __('Currency') }}">
            <i class="fas fa-dollar-sign mr-1"></i>
            @php
                $currentCurrency = collect($navCurrencies)->firstWhere('is_current', true);
            @endphp
            <span class="hidden sm:inline" x-show="!currencyOpen">{{ $currentCurrency?->symbol ?? '$' }} {{ $currentCurrency?->code ?? 'USD' }}</span>
        </button>
        <div
            x-show="currencyOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg py-2 z-50 border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto"
            style="display: none;">
            <form method="POST" action="{{ route('locale.currency') }}" id="currency-form" class="flex flex-col">
                @csrf
                @foreach($navCurrencies as $currency)
                    <label class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer block w-full">
                        <input type="radio" name="currency" value="{{ $currency->code }}" {{ $currency->is_current ? 'checked' : '' }} onchange="document.getElementById('currency-form').submit();" class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $currency->symbol }} {{ $currency->code }}</span>
                    </label>
                @endforeach
            </form>
        </div>
    </div>
</div>

