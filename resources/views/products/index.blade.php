<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}
        .card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
        .title{font-weight:600;margin:0 0 6px}
        .price{color:#0f766e}
        nav{margin-top:1rem}
        a{color:#2563eb;text-decoration:none}
        form input{padding:.5rem;border:1px solid #d1d5db;border-radius:6px}
        form button{padding:.5rem .75rem;border-radius:6px;background:#2563eb;color:#fff;border:0}
    </style>
    </head>
<body>
    <header>
        <h1>Products</h1>
        <form method="get" action="{{ route('products.search') }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search products">
            <button type="submit">Search</button>
        </form>
    </header>
    
    @php
        $hasQuery = filled(request('q'));
        $hasCategory = filled(request('category'));
        $hasBrand = filled(request('brand'));
        $hasActiveFilters = $hasQuery || $hasCategory || $hasBrand;
    @endphp

    @if($hasActiveFilters)
        <section style="margin-bottom:1rem;padding:.75rem;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb">
            @if($hasQuery)
                <div>Showing results for: "{{ request('q') }}"</div>
            @endif
            @if($hasCategory || $hasBrand)
                <div>
                    Filters:
                    @if($hasCategory)
                        <span>Category: {{ request('category') }}</span>
                    @endif
                    @if($hasBrand)
                        <span style="margin-left:.5rem">Brand: {{ request('brand') }}</span>
                    @endif
                </div>
            @endif
            <div style="margin-top:.5rem">
                <a href="{{ route('products.index') }}">Clear filters</a>
            </div>
        </section>
    @endif

    @if(isset($products) && $products->count())
        <div class="grid">
            @foreach($products as $product)
                <article class="card">
                    <h2 class="title">
                        <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                    </h2>
                    @if(!is_null($product->price))
                        <div class="price">${{ number_format((float)$product->price, 2) }}</div>
                    @endif
                </article>
            @endforeach
        </div>
        <nav>
            {{ $products->withQueryString()->links() }}
        </nav>
    @else
        <p>No products found.</p>
    @endif
</body>
</html>
