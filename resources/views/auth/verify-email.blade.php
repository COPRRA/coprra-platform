<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .message {
            text-align: center;
            margin: 20px 0;
            color: #666;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Verification Required</h1>
        <div class="message">
            <p>Please verify your email address before continuing.</p>
            <p>Check your email for a verification link.</p>
        </div>
        <div style="text-align: center;">
            @if (session('status'))
                <div style="background: #d1fae5; color: #10b981; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('verification.send') }}" style="display: inline-block;">
                @csrf
                <button type="submit" class="btn" style="border: none; cursor: pointer;">Resend Verification Email</button>
            </form>
            <a href="{{ route('home') }}" class="btn">Go Home</a>
        </div>
    </div>
</body>
</html>
