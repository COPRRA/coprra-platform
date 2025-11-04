@extends('layouts.app')

@section('title', 'Compare Products')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center mb-4">
            <h1>Product Comparison</h1>
            <form method="POST" action="{{ route('compare.clear') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">Clear Comparison</button>
            </form>
        </div>
    </div>

    @if(($products ?? collect())->count() > 0)
        <div class="row">
            @foreach($products as $product)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100">
                        @if($product->image)
                            <img src="{{ $product->image }}" class="card-img-top" alt="{{ $product->name }}" style="height:200px;object-fit:cover;">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $product->name }}</h5>
                            <p class="card-text small text-muted">
                                <span class="badge bg-secondary">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                @if($product->brand)
                                    <span class="badge bg-info">{{ $product->brand->name }}</span>
                                @endif
                            </p>
                            <p class="card-text"><strong class="text-primary">${{ number_format($product->price, 2) }}</strong></p>
                            <div class="mt-auto d-flex justify-content-between">
                                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-primary btn-sm">View Details</a>
                                <form method="POST" action="{{ route('compare.remove', $product) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
            <h3>No products to compare</h3>
            <p class="text-muted">Browse products and add up to 4 items to compare.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Browse Products</a>
        </div>
    @endif
</div>
@endsection
