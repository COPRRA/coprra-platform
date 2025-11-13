<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/brands.min.css" rel="stylesheet">
    <style>body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}</style>
    </head>
<body>
    <h1>Registration</h1>
    <p>Registration is currently disabled.</p>
    <p><a href="{{ route('login') }}">Go to Login</a></p>
    
    <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid #e5e7eb">
        <p style="text-align:center;margin-bottom:1rem;color:#6b7280;font-size:0.875rem">Or continue with</p>
        <div style="display:flex;gap:0.75rem;flex-direction:column;max-width:380px">
            <a href="{{ route('auth.google.redirect') }}" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.6rem;border-radius:6px;background:#fff;color:#111;border:1px solid #d1d5db;text-decoration:none;transition:background 0.2s">
                <i class="fab fa-google" style="color:#4285f4"></i>
                <span>Sign up with Google</span>
            </a>
            <a href="{{ route('auth.facebook.redirect') }}" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.6rem;border-radius:6px;background:#1877f2;color:#fff;border:0;text-decoration:none;transition:background 0.2s">
                <i class="fab fa-facebook-f"></i>
                <span>Sign up with Facebook</span>
            </a>
        </div>
    </div>
</body>
</html>
