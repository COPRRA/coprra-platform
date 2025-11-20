@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">Edit Product #{{ $product->id }}</h2>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.update', $product) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Brand</label>
                    <p class="form-control-plaintext">{{ $product->brand->name ?? '—' }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <p class="form-control-plaintext">{{ $product->category->name ?? '—' }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Last Updated</label>
                    <p class="form-control-plaintext">{{ $product->updated_at?->toDayDateTimeString() }}</p>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Product</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to Products</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
