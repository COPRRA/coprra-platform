<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    {{-- Use local Font Awesome instead of CDN --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .card{max-width:380px;border:1px solid #e5e7eb;border-radius:8px;padding:16px}
        label{display:block;margin:.5rem 0 .25rem}
        input{width:100%;padding:.5rem;border:1px solid #d1d5db;border-radius:6px}
        button{margin-top:1rem;width:100%;padding:.6rem;border-radius:6px;background:#2563eb;color:#fff;border:0}
        .error{color:#ef4444;margin-top:.5rem}
        .password-wrapper{position:relative}
        .password-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6b7280}
        .password-toggle:hover{color:#2563eb}
    </style>
    </head>
<body>
    <h1>Login</h1>
    <div class="card">
        <form method="post" action="{{ route('login.post') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>
            @error('email')<div class="error">{{ $message }}</div>@enderror

            <label for="password">Password</label>
            <div class="password-wrapper">
                <input id="password" name="password" type="password" autocomplete="current-password" required style="padding-right:2.5rem">
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>
            @error('password')<div class="error">{{ $message }}</div>@enderror

            <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem">
                <input type="checkbox" name="remember"> Remember me
            </label>

            <button type="submit">Sign in</button>

            <div style="margin-top:1rem;text-align:center">
                <a href="{{ route('password.request') }}" style="color:#2563eb;text-decoration:none;font-size:0.875rem">
                    Forgot your password?
                </a>
            </div>
        </form>

        <div style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid #e5e7eb">
            <p style="text-align:center;margin-bottom:1rem;color:#6b7280;font-size:0.875rem">Or continue with</p>
            <div style="display:flex;gap:0.75rem;flex-direction:column">
                <a href="{{ route('auth.google.redirect') }}" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.6rem;border-radius:6px;background:#fff;color:#111;border:1px solid #d1d5db;text-decoration:none;transition:background 0.2s">
                    <i class="fab fa-google" style="color:#4285f4"></i>
                    <span>Login with Google</span>
                </a>
                <a href="{{ route('auth.facebook.redirect') }}" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.6rem;border-radius:6px;background:#1877f2;color:#fff;border:0;text-decoration:none;transition:background 0.2s">
                    <i class="fab fa-facebook-f"></i>
                    <span>Login with Facebook</span>
                </a>
            </div>
        </div>
    </div>
    <script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
