@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Products</h2>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->brand->name ?? '—' }}</td>
                            <td>{{ $product->category->name ?? '—' }}</td>
                            <td>{{ $product->updated_at?->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No products found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

