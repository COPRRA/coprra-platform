<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}
        .card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
        a{color:#2563eb;text-decoration:none}
        form button{padding:.35rem .6rem;border-radius:6px;background:#ef4444;color:#fff;border:0}
    </style>
    </head>
<body>
    <h1>Your Wishlist</h1>
    @if(isset($wishlistItems) && $wishlistItems->count())
        <div class="grid">
            @foreach($wishlistItems as $item)
                <article class="card">
                    <h2>
                        <a href="{{ route('products.show', $item->product->slug) }}">{{ $item->product->name }}</a>
                    </h2>
                    <form method="post" action="{{ route('wishlist.remove', $item->product_id) }}">
                        @csrf
                        <button type="submit">Remove</button>
                    </form>
                </article>
            @endforeach
        </div>
    @else
        <p>Your wishlist is empty.</p>
    @endif
</body>
</html>
