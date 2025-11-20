# Content Security Policy (CSP) Configuration

## Overview

The Content Security Policy is configured in Laravel middleware to allow dynamic nonce generation per request, providing better security than static CSP headers.

## Current Configuration

### Primary CSP Handler
**File:** `app/Http/Middleware/AddCspNonce.php`

This middleware:
- Generates a unique nonce for each request
- Sets CSP header with nonce support for scripts
- Runs early in the middleware stack (before SecurityHeadersMiddleware)

### CSP Policy Details

The current CSP policy includes:
- `default-src 'self'` - Default source restriction
- `script-src 'self' 'nonce-{nonce}'` - Scripts from same origin with nonce
- `style-src 'self' 'unsafe-inline'` - Styles from same origin, inline allowed
- `img-src 'self' data: https:` - Images from same origin, data URIs, and HTTPS
- `font-src 'self' data:` - Fonts from same origin and data URIs
- `connect-src 'self'` - AJAX/fetch from same origin
- `frame-ancestors 'none'` - No framing allowed
- `base-uri 'self'` - Base tag restricted to same origin
- `form-action 'self'` - Forms submit to same origin
- `object-src 'none'` - No object/embed tags

### Using Nonce in Views

To use the CSP nonce in Blade templates:

```blade
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    // Your inline script
</script>
```

## Conflict Resolution

**Issue:** CSP was previously defined in both `public/.htaccess` and Laravel middleware, causing conflicts.

**Solution:** Removed static CSP from `.htaccess` (line 59) to allow middleware to handle CSP dynamically.

**Date:** 2025-01-27

## Middleware Order

The middleware stack order (from `bootstrap/app.php`):
1. `AddCspNonce` - Sets CSP with nonce
2. `SecurityHeadersMiddleware` - Sets other security headers (does not set CSP)

## Notes

- CSP is now managed entirely by Laravel middleware
- Nonce generation ensures better security for inline scripts
- Static CSP in `.htaccess` has been removed to prevent conflicts

