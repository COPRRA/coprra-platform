<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:Inter,system-ui,Arial,sans-serif;margin:2rem;color:#111}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}
        .card{border:1px solid #e5e7eb;border-radius:8px;padding:12px}
        a{color:#2563eb;text-decoration:none}
    </style>
    </head>
<body>
    <h1>Brands</h1>
    @if(isset($brands) && $brands->count())
        <div class="grid">
            @foreach($brands as $brand)
                <article class="card">
                    <h2>{{ $brand->name }}</h2>
                    @if(!empty($brand->website_url))
                        <p><a href="{{ $brand->website_url }}" target="_blank">Website</a></p>
                    @endif
                    @if(!empty($brand->description))
                        <p>{{ $brand->description }}</p>
                    @endif
                </article>
            @endforeach
        </div>
        {{ $brands->links() }}
    @else
        <p>No brands found.</p>
    @endif
</body>
</html>
