# Google Analytics Integration Documentation

**Date:** 2025-01-27  
**Status:** ✅ Verified and Active

## Overview

Google Analytics is integrated into the COPRRA platform to track user behavior, page views, and website analytics.

## Integration Location

### Primary Integration

**File:** `resources/views/layouts/app.blade.php`  
**Lines:** 67-76

```blade
<!-- Google Analytics -->
@if(config('services.google_analytics.id'))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ config('services.google_analytics.id') }}');
</script>
@endif
```

### Configuration

**File:** `config/services.php`  
**Lines:** 77-79

```php
'google_analytics' => [
    'id' => env('GOOGLE_ANALYTICS_ID'),
],
```

**File:** `config/coprra.php`  
**Lines:** 124-128

```php
'analytics' => [
    'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    'track_user_behavior' => env('TRACK_USER_BEHAVIOR', true),
    'track_price_clicks' => env('TRACK_PRICE_CLICKS', true),
],
```

## Environment Variable

**Variable Name:** `GOOGLE_ANALYTICS_ID`

**Location:** `.env` file

**Format:** Google Analytics Measurement ID (e.g., `G-XXXXXXXXXX`)

**Example:**
```env
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
```

## How It Works

1. **Conditional Loading:** The GA script only loads if `GOOGLE_ANALYTICS_ID` is configured
2. **Async Loading:** Uses `async` attribute for non-blocking script loading
3. **Global Site Tag (gtag.js):** Uses Google's recommended gtag.js library
4. **Automatic Configuration:** Automatically configures GA with the provided ID

## Verification Steps

### 1. Check Environment Variable

```bash
# On production server
grep GOOGLE_ANALYTICS_ID .env
```

### 2. Verify Configuration

```php
// In Laravel Tinker
config('services.google_analytics.id')
```

### 3. Check Rendered HTML

Visit any page and view source. Look for:
- `gtag/js?id=G-XXXXXXXXXX` script tag
- `gtag('config', 'G-XXXXXXXXXX')` call

### 4. Test in Browser

1. Open browser DevTools (F12)
2. Go to Network tab
3. Filter by "gtag" or "google-analytics"
4. Reload page
5. Verify requests to `googletagmanager.com`

### 5. Verify in Google Analytics Dashboard

1. Log in to Google Analytics
2. Check Real-Time reports
3. Visit the website
4. Verify events appear in Real-Time

## Integration Status

✅ **Verified:** Integration code is present and correct  
✅ **Location:** `resources/views/layouts/app.blade.php` (lines 67-76)  
✅ **Configuration:** Properly configured in `config/services.php`  
✅ **Conditional:** Only loads when `GOOGLE_ANALYTICS_ID` is set  
✅ **Best Practices:** Uses async loading and gtag.js

## Notes

- The integration uses Google's recommended gtag.js library
- Script loads asynchronously to avoid blocking page rendering
- Integration is conditional - won't load if ID is not configured
- No additional configuration needed beyond setting the environment variable

## Troubleshooting

### GA Not Loading

1. **Check Environment Variable:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

2. **Verify .env File:**
   - Ensure `GOOGLE_ANALYTICS_ID` is set
   - No typos in variable name
   - Value is correct GA Measurement ID

3. **Check Browser Console:**
   - Look for JavaScript errors
   - Verify script tag is present in HTML
   - Check network requests

### GA Loading But Not Tracking

1. **Check GA Dashboard:**
   - Verify property is active
   - Check filters and views
   - Ensure Real-Time reports are enabled

2. **Verify Script:**
   - Check that gtag.js loads successfully
   - Verify config call executes
   - Check for ad blockers (may block GA)

3. **Check CSP:**
   - Ensure Content Security Policy allows `googletagmanager.com`
   - Check browser console for CSP violations

## Related Files

- `resources/views/layouts/app.blade.php` - Main layout with GA integration
- `config/services.php` - Service configuration
- `config/coprra.php` - Analytics configuration
- `.env` - Environment variables
- `.env.example` - Example environment file

## References

- [Google Analytics Documentation](https://developers.google.com/analytics/devguides/collection/gtagjs)
- [gtag.js Setup Guide](https://developers.google.com/analytics/devguides/collection/gtagjs)

