@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="display-6">{{ $stats['users'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Products</h5>
                    <p class="display-6">{{ $stats['products'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Categories</h5>
                    <p class="display-6">{{ $stats['categories'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Stores</h5>
                    <p class="display-6">{{ $stats['stores'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Recent Users</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentUsers as $u)
                                <tr>
                                    <td>{{ $u->id }}</td>
                                    <td>{{ $u->email }}</td>
                                    <td>{{ $u->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No users</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Recent Products</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentProducts as $p)
                                <tr>
                                    <td>{{ $p->id }}</td>
                                    <td>{{ $p->name }}</td>
                                    <td>{{ $p->updated_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No products</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

