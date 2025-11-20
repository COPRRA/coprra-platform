@if(!\Illuminate\Support\Facades\Cookie::has('cookie_consent'))
<div id="cookie-consent-banner" class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg" x-data="{ show: true }" x-show="show" x-transition>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ __('Cookie Consent') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.') }}
                    <a href="{{ route('privacy') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                        {{ __('Learn more') }}
                    </a>
                </p>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    onclick="acceptCookies()"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
                >
                    {{ __('Accept All') }}
                </button>
                <button
                    type="button"
                    onclick="declineCookies()"
                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition text-sm font-medium"
                >
                    {{ __('Decline') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
function acceptCookies() {
    document.cookie = 'cookie_consent=accepted; path=/; max-age=31536000'; // 1 year
    document.getElementById('cookie-consent-banner').style.display = 'none';
}

function declineCookies() {
    document.cookie = 'cookie_consent=declined; path=/; max-age=31536000'; // 1 year
    document.getElementById('cookie-consent-banner').style.display = 'none';
}
</script>
@endif

