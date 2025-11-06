{{-- Hierarchical Internationalization Component with Alpine.js --}}
<div x-data="i18nComponent()" x-init="init()" class="flex items-center space-x-2 border-l border-gray-300 dark:border-gray-600 pl-4">
    <!-- Language Dropdown -->
    <div class="relative">
        <select
            x-model="selectedLanguage"
            @change="onLanguageChange()"
            class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
            @foreach($navLanguages as $language)
                <option value="{{ $language->code }}">
                    {{ $language->native_name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Country Dropdown (Dynamically Filtered) -->
    <div class="relative">
        <select
            x-model="selectedCountry"
            @change="onCountryChange()"
            class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
            :disabled="filteredCountries.length === 0">
            <option value="">{{ __('Select Country') }}</option>
            <template x-for="country in filteredCountries" :key="country.code">
                <option :value="country.code" x-text="`${country.flag || ''} ${country.name}`"></option>
            </template>
        </select>
    </div>

    <!-- Currency Display (Auto-Updated) -->
    <div class="relative">
        <input
            type="text"
            :value="`${selectedCurrencySymbol} ${selectedCurrency}`"
            readonly
            class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 cursor-not-allowed w-24 text-center"
            title="Currency is automatically set based on selected country">
    </div>
</div>

<script>
function i18nComponent() {
    return {
        // Hierarchical data from backend
        hierarchy: @json($i18nHierarchy['hierarchy'] ?? []),
        current: @json($i18nHierarchy['current'] ?? ['language' => 'en', 'country' => null, 'currency' => 'USD']),

        // Selected values
        selectedLanguage: '{{ app()->getLocale() }}',
        selectedCountry: '{{ session("locale_country", "") }}',
        selectedCurrency: '{{ session("currency", config("app.default_currency", "USD")) }}',
        selectedCurrencySymbol: '$',

        // Filtered countries based on language
        filteredCountries: [],

        init() {
            // Set initial state from session
            this.selectedLanguage = this.current.language || 'en';
            this.selectedCountry = this.current.country || '';
            this.selectedCurrency = this.current.currency || 'USD';

            // Filter countries for current language
            this.filterCountriesByLanguage();

            // Update currency symbol
            this.updateCurrencySymbol();
        },

        filterCountriesByLanguage() {
            // Get countries for selected language
            this.filteredCountries = this.hierarchy[this.selectedLanguage] || [];

            // If current country is not in filtered list, reset it
            const countryExists = this.filteredCountries.some(c => c.code === this.selectedCountry);
            if (!countryExists && this.filteredCountries.length > 0) {
                // Auto-select first country if none selected
                if (!this.selectedCountry) {
                    this.selectedCountry = this.filteredCountries[0].code;
                    this.selectedCurrency = this.filteredCountries[0].currency_code;
                    this.selectedCurrencySymbol = this.filteredCountries[0].currency_symbol;
                }
            }
        },

        onLanguageChange() {
            // Filter countries based on new language
            this.filterCountriesByLanguage();

            // Submit language change to server
            this.submitLanguageChange();
        },

        onCountryChange() {
            if (!this.selectedCountry) return;

            // Find selected country data
            const countryData = this.filteredCountries.find(c => c.code === this.selectedCountry);

            if (countryData) {
                // Auto-update currency
                this.selectedCurrency = countryData.currency_code;
                this.selectedCurrencySymbol = countryData.currency_symbol;

                // Submit changes to server
                this.submitCountryChange();
                this.submitCurrencyChange();
            }
        },

        updateCurrencySymbol() {
            // Find current country to get currency symbol
            if (this.selectedCountry) {
                const countryData = this.filteredCountries.find(c => c.code === this.selectedCountry);
                if (countryData) {
                    this.selectedCurrencySymbol = countryData.currency_symbol;
                }
            }
        },

        submitLanguageChange() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("locale.language") }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';

            const langInput = document.createElement('input');
            langInput.type = 'hidden';
            langInput.name = 'language';
            langInput.value = this.selectedLanguage;

            form.appendChild(csrfInput);
            form.appendChild(langInput);
            document.body.appendChild(form);
            form.submit();
        },

        submitCountryChange() {
            // Submit via fetch to avoid page reload
            fetch('{{ route("locale.country") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    country: this.selectedCountry
                })
            }).catch(error => console.error('Country update failed:', error));
        },

        submitCurrencyChange() {
            // Submit via fetch to avoid page reload
            fetch('{{ route("locale.currency") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    currency: this.selectedCurrency
                })
            }).catch(error => console.error('Currency update failed:', error));
        }
    }
}
</script>
