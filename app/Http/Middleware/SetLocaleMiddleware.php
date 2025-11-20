<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Country;
use App\Models\Language;
use App\Services\GeoLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocaleMiddleware
{
    protected GeoLocationService $geoService;

    public function __construct(GeoLocationService $geoService)
    {
        $this->geoService = $geoService;
    }

    public function handle(Request $request, \Closure $next)
    {
        // 1. Check if locale is already in session
        if (Session::has('locale_language') && Session::has('locale_currency') && Session::has('locale_country')) {
            $this->applySessionLocale();

            return $next($request);
        }

        // 2. Try to detect from IP
        $ipAddress = $request->ip();
        $detectedCountryCode = $this->geoService->getCountryFromIp($ipAddress);

        // 3. If country detected, find it in database
        if ($detectedCountryCode) {
            $country = Country::where('code', $detectedCountryCode)
                ->with(['language', 'currency'])
                ->active()
                ->first()
            ;

            if ($country) {
                $this->setLocale($country->language->code, $country->code, $country->currency->code);

                return $next($request);
            }
        }

        // 4. Fallback to default (English, US, USD)
        $this->setDefaultLocale();

        return $next($request);
    }

    /**
     * Apply locale from session.
     */
    private function applySessionLocale(): void
    {
        $languageCode = Session::get('locale_language', 'en');
        App::setLocale($languageCode);

        // Set HTML direction based on language
        $language = Language::where('code', $languageCode)->first();
        if ($language && 'rtl' === $language->direction) {
            view()->share('dir', 'rtl');
        } else {
            view()->share('dir', 'ltr');
        }
    }

    /**
     * Set locale and store in session.
     */
    private function setLocale(string $languageCode, string $countryCode, string $currencyCode): void
    {
        Session::put('locale_language', $languageCode);
        Session::put('locale_country', $countryCode);
        Session::put('locale_currency', $currencyCode);

        App::setLocale($languageCode);

        // Set HTML direction
        $language = Language::where('code', $languageCode)->first();
        if ($language && 'rtl' === $language->direction) {
            view()->share('dir', 'rtl');
        } else {
            view()->share('dir', 'ltr');
        }
    }

    /**
     * Set default locale (English, US, USD).
     */
    private function setDefaultLocale(): void
    {
        $this->setLocale('en', 'US', 'USD');
    }
}
