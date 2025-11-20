<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; }
        .sidebar { min-width: 220px; }
        .nav-link.active { font-weight: 600; }
    </style>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar bg-light border-end p-3">
        <h5 class="mb-3">Admin</h5>
        <div class="list-group list-group-flush">
            <a class="list-group-item list-group-item-action" href="{{ route('admin.dashboard') }}">Dashboard</a>
            <a class="list-group-item list-group-item-action" href="{{ route('admin.products.index') }}">Products</a>
            <a class="list-group-item list-group-item-action" href="{{ route('admin.categories.index') }}">Categories</a>
            <a class="list-group-item list-group-item-action" href="{{ route('admin.brands.index') }}">Brands</a>
        </div>

        <hr>
        <div class="small text-muted">Logged in as {{ auth()->user()->email ?? 'Guest' }}</div>
    </nav>

    <main class="flex-grow-1 p-4">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

