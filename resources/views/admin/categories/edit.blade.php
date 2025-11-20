@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">Edit Category #{{ $category->id }}</h2>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Category Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $category->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <p class="form-control-plaintext">{{ $category->slug }}</p>
                </div>

                <div class="mb-3">
                    <label class="form-label">Products Count</label>
                    <p class="form-control-plaintext">{{ $category->products()->count() }}</p>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Category</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
