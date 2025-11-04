<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}
        .card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
        .count{color:#6b7280}
        a{color:#2563eb;text-decoration:none}
        nav{margin-top:1rem}
    </style>
    </head>
<body>
    <h1>Categories</h1>
    @if(isset($categories) && $categories->count())
        <div class="grid">
            @foreach($categories as $category)
                <article class="card">
                    <h2><a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a></h2>
                    <div class="count">Products: {{ $category->products_count ?? $category->products()->count() }}</div>
                </article>
            @endforeach
        </div>
        <nav>
            {{ $categories->withQueryString()->links() }}
        </nav>
    @else
        <p>No categories found.</p>
    @endif
</body>
</html>
