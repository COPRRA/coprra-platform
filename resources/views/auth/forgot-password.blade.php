<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    {{-- Use local Font Awesome instead of CDN --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .card{max-width:380px;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin:0 auto}
        label{display:block;margin:.5rem 0 .25rem}
        input{width:100%;padding:.5rem;border:1px solid #d1d5db;border-radius:6px}
        button{margin-top:1rem;width:100%;padding:.6rem;border-radius:6px;background:#2563eb;color:#fff;border:0}
        .error{color:#ef4444;margin-top:.5rem}
        .success{color:#10b981;margin-top:.5rem;background:#d1fae5;padding:.5rem;border-radius:6px}
        .back-link{margin-top:1rem;text-align:center}
        .back-link a{color:#2563eb;text-decoration:none;font-size:0.875rem}
    </style>
</head>
<body>
    <h1 style="text-align:center">Reset Password</h1>
    <div class="card">
        @if (session('status'))
            <div class="success">
                {{ session('status') }}
            </div>
        @endif

        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" placeholder="Email address" required>
            @error('email')<div class="error">{{ $message }}</div>@enderror

            <button type="submit">Send Password Reset Link</button>
        </form>

        <div class="back-link">
            <a href="{{ route('login') }}">
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>

