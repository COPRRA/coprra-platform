@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-3">Edit Category #{{ $category->id }}</h2>
    <p class="text-muted">This is a placeholder page. Full edit functionality will be implemented in the next phase.</p>

    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $category->name }}</dd>

                <dt class="col-sm-3">Slug</dt>
                <dd class="col-sm-9">{{ $category->slug }}</dd>

                <dt class="col-sm-3">Products</dt>
                <dd class="col-sm-9">{{ $category->products()->count() }}</dd>
            </dl>

            <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
        </div>
    </div>
</div>
@endsection

