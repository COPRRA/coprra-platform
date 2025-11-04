<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name ?? 'Product' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .price{color:#0f766e}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-top:1rem}
        .card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
        a{color:#2563eb;text-decoration:none}
    </style>
    </head>
<body>
    <a href="{{ route('products.index') }}">← Back to products</a>
    <h1>{{ $product->name }}</h1>
    @if(!is_null($product->price))
        <div class="price">${{ number_format((float)$product->price, 2) }}</div>
    @endif
    @if(!empty($product->description))
        <p>{{ $product->description }}</p>
    @endif

    @if(isset($relatedProducts) && $relatedProducts->count())
        <h2>Related Products</h2>
        <div class="grid">
            @foreach($relatedProducts as $rp)
                <article class="card">
                    <h3><a href="{{ route('products.show', $rp->slug) }}">{{ $rp->name }}</a></h3>
                    @if(!is_null($rp->price))
                        <div class="price">${{ number_format((float)$rp->price, 2) }}</div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</body>
</html>
