<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $category->name ?? 'Category' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-top:1rem}
        .card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
        a{color:#2563eb;text-decoration:none}
    </style>
    </head>
<body>
    <a href="{{ route('categories.index') }}">← Back to categories</a>
    <h1>{{ $category->name }}</h1>
    @if(!empty($category->description))
        <p>{{ $category->description }}</p>
    @endif

    @if(isset($products) && $products->count())
        <h2>Products</h2>
        <div class="grid">
            @foreach($products as $product)
                <article class="card">
                    <h3><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h3>
                </article>
            @endforeach
        </div>
        {{ $products->links() }}
    @else
        <p>No products in this category.</p>
    @endif
</body>
</html>
