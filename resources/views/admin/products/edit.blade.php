@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">Edit Product #{{ $product->id }}</h2>
    <p class="text-muted">This is a placeholder page. Full edit functionality will be implemented in the next phase.</p>

    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $product->name }}</dd>

                <dt class="col-sm-3">Brand</dt>
                <dd class="col-sm-9">{{ $product->brand->name ?? '—' }}</dd>

                <dt class="col-sm-3">Category</dt>
                <dd class="col-sm-9">{{ $product->category->name ?? '—' }}</dd>

                <dt class="col-sm-3">Updated</dt>
                <dd class="col-sm-9">{{ $product->updated_at?->toDayDateTimeString() }}</dd>
            </dl>

            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Back to Products</a>
        </div>
    </div>
</div>
@endsection

