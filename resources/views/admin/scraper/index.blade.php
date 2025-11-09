@extends('layouts.app')

@section('title', 'Content Aggregation Engine - Scraper')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">🏭 محرك استيراد المحتوى</h1>
            <p class="text-gray-600">Content Aggregation Engine - Automatic Product Import from ANY Store Worldwide</p>
            <div class="mt-2 flex gap-2 text-sm text-gray-500">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded">✅ Amazon (all countries)</span>
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded">✅ Jumia</span>
                <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded">✅ Noon</span>
                <span class="bg-orange-100 text-orange-800 px-3 py-1 rounded">✅ Any Store</span>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <strong>✅ Success!</strong> {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <strong>❌ Error!</strong> {{ session('error') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Scraper Input Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">📝 URL Input</h2>

                <form action="{{ route('admin.scraper.start') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="urls" class="block text-sm font-medium text-gray-700 mb-2">
                            Enter Product URLs from ANY Store (one per line):
                        </label>
                        <textarea
                            id="urls"
                            name="urls"
                            rows="12"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                            placeholder="https://www.amazon.eg/-/en/Product-Name/dp/XXXXXXXXXX/&#10;https://www.amazon.com/Product-Name/dp/YYYYYYYYYY/&#10;https://www.jumia.com.eg/product-name.html&#10;https://www.noon.com/egypt-en/product/&#10;Any store URL works!"
                            required
                        >{{ old('urls') }}</textarea>
                        @error('urls')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                            </svg>
                            Start Scraping Batch
                        </button>
                    </div>
                </form>

                <!-- Database Statistics -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">📊 Database Statistics</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Products</p>
                            <p class="text-2xl font-bold text-blue-600">{{ \App\Models\Product::count() }}</p>
                        </div>
                        <div class="bg-green-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Categories</p>
                            <p class="text-2xl font-bold text-green-600">{{ \App\Models\Category::count() }}</p>
                        </div>
                        <div class="bg-purple-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Brands</p>
                            <p class="text-2xl font-bold text-purple-600">{{ \App\Models\Brand::count() }}</p>
                        </div>
                        <div class="bg-orange-50 p-3 rounded">
                            <p class="text-sm text-gray-600">Stores</p>
                            <p class="text-2xl font-bold text-orange-600">{{ \App\Models\Store::count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold mb-3">ℹ️ Instructions</h3>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li class="flex items-start">
                            <span class="mr-2">✅</span>
                            <span><strong>Any Store Worldwide:</strong> Amazon (all countries), Jumia, Noon, or any other online store</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">✅</span>
                            <span><strong>Any Product Type:</strong> Electronics, appliances, fashion, books, furniture - everything is supported</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">•</span>
                            <span>One URL per line</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">•</span>
                            <span>Scraping happens in background - check status dashboard for progress</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">💡</span>
                            <span><strong>Tip:</strong> Images are not imported automatically. You can add them manually later from /admin/products</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Real-Time Logs Section -->
            <div class="bg-gray-900 rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gray-800 px-6 py-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">📋 Scraping Logs (Real-Time)</h2>
                    <div class="flex gap-2">
                        <button
                            onclick="refreshLogs()"
                            class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm transition"
                        >
                            🔄 Refresh
                        </button>
                        <form action="{{ route('admin.scraper.clear-logs') }}" method="POST" class="inline">
                            @csrf
                            <button
                                type="submit"
                                class="bg-red-700 hover:bg-red-600 text-white px-4 py-2 rounded text-sm transition"
                                onclick="return confirm('Clear all logs?')"
                            >
                                🗑️ Clear
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-6">
                    <pre id="log-display" class="text-green-400 text-sm font-mono h-96 overflow-y-auto bg-black bg-opacity-50 p-4 rounded">@if(!empty($recentLogs))@foreach(array_reverse($recentLogs) as $log){{ $log }}@endforeach@else[{{ date('Y-m-d H:i:s') }}] No logs yet. Start a scraping batch to see progress here.
[{{ date('Y-m-d H:i:s') }}] System ready and waiting for URLs...@endif</pre>
                </div>

                <!-- Auto-refresh indicator -->
                <div class="px-6 pb-4">
                    <div class="flex items-center text-gray-400 text-xs">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                        <span>Auto-refreshing every 5 seconds</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Status Dashboard -->
        <div class="mt-8 bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">📊 Status Dashboard</h2>
                    <p class="text-blue-100 text-sm">Real-time tracking of all scraping jobs</p>
                </div>
                <div class="flex gap-2">
                    <button
                        onclick="refreshJobs()"
                        class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded text-sm transition"
                    >
                        🔄 Refresh Status
                    </button>
                    <form action="{{ route('admin.scraper.clear-jobs') }}" method="POST" class="inline">
                        @csrf
                        <button
                            type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm transition"
                            onclick="return confirm('Clear completed and failed jobs?')"
                        >
                            🗑️ Clear History
                        </button>
                    </form>
                </div>
            </div>

            <!-- Job Statistics -->
            <div class="grid grid-cols-4 gap-6 p-6 border-b border-gray-200" id="job-stats">
                <div class="text-center">
                    <p class="text-3xl font-bold text-gray-800" id="stat-total">{{ $stats['total_jobs'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">Total Jobs</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600" id="stat-completed">{{ $stats['completed_jobs'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">✅ Completed</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-red-600" id="stat-failed">{{ $stats['failed_jobs'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">❌ Failed</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl font-bold text-yellow-600" id="stat-pending">{{ $stats['pending_jobs'] }}</p>
                    <p class="text-sm text-gray-600 mt-1">⏳ Pending</p>
                </div>
            </div>

            <!-- Jobs Table -->
            <div class="overflow-x-auto">
                <table class="w-full" id="jobs-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Error</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="jobs-tbody">
                        @forelse($recentJobs as $job)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $job->batch_id }}-{{ $job->job_number }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->status === 'completed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        ✅ Completed
                                    </span>
                                @elseif($job->status === 'failed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        ❌ Failed
                                    </span>
                                @elseif($job->status === 'running')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        ⏳ Running
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        ⏸️ Queued
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <a href="{{ $job->url }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    {{ Str::limit($job->url, 50) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($job->product)
                                    <a href="{{ route('products.show', $job->product) }}" class="text-green-600 hover:text-green-800 font-medium">
                                        {{ Str::limit($job->product->name, 30) }}
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $job->created_at->diffForHumans() }}</div>
                                @if($job->getDuration())
                                    <div class="text-xs text-gray-400">{{ $job->getDuration() }}s</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-red-600 max-w-xs truncate">
                                {{ $job->error_message ? Str::limit($job->error_message, 40) : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No scraping jobs yet. Submit some URLs above to get started!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Auto-refresh indicator -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></div>
                        <span>Dashboard auto-refreshing every 3 seconds</span>
                    </div>
                    <span id="last-update">Last updated: {{ now()->format('H:i:s') }}</span>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// Auto-refresh logs every 5 seconds
let autoRefreshInterval = null;
let jobsRefreshInterval = null;

function refreshLogs() {
    fetch('{{ route("admin.scraper.logs") }}')
        .then(response => response.json())
        .then(data => {
            const logDisplay = document.getElementById('log-display');
            if (data.logs && data.logs.length > 0) {
                logDisplay.textContent = data.logs.reverse().join('');
            } else {
                logDisplay.textContent = '[' + new Date().toISOString() + '] No logs yet. Start a scraping batch to see progress here.\n[' + new Date().toISOString() + '] System ready and waiting for URLs...';
            }
            // Scroll to bottom
            logDisplay.scrollTop = logDisplay.scrollHeight;
        })
        .catch(error => {
            console.error('Error fetching logs:', error);
        });
}

function refreshJobs() {
    fetch('{{ route("admin.scraper.jobs") }}')
        .then(response => response.json())
        .then(data => {
            // Update statistics
            document.getElementById('stat-total').textContent = data.stats.total_jobs;
            document.getElementById('stat-completed').textContent = data.stats.completed_jobs;
            document.getElementById('stat-failed').textContent = data.stats.failed_jobs;
            document.getElementById('stat-pending').textContent = data.stats.pending_jobs;

            // Update jobs table
            const tbody = document.getElementById('jobs-tbody');
            if (data.jobs && data.jobs.length > 0) {
                tbody.innerHTML = data.jobs.map(job => {
                    const statusBadge = getStatusBadge(job.status);
                    const productLink = job.product_id ?
                        `<a href="/products/${job.product_id}" class="text-green-600 hover:text-green-800 font-medium">${truncate(job.product_name, 30)}</a>` :
                        '<span class="text-gray-400">-</span>';

                    return `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${job.batch_id}-${job.job_number}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                <a href="${job.url}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    ${truncate(job.url, 50)}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm">${productLink}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>${job.created_at}</div>
                                ${job.duration ? `<div class="text-xs text-gray-400">${job.duration}s</div>` : ''}
                            </td>
                            <td class="px-6 py-4 text-sm text-red-600 max-w-xs truncate">
                                ${job.error_message ? truncate(job.error_message, 40) : '-'}
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No scraping jobs yet. Submit some URLs above to get started!
                        </td>
                    </tr>
                `;
            }

            // Update last update time
            document.getElementById('last-update').textContent = 'Last updated: ' + new Date().toLocaleTimeString();
        })
        .catch(error => {
            console.error('Error fetching jobs:', error);
        });
}

function getStatusBadge(status) {
    const badges = {
        'completed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">✅ Completed</span>',
        'failed': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">❌ Failed</span>',
        'running': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">⏳ Running</span>',
        'queued': '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">⏸️ Queued</span>'
    };
    return badges[status] || badges['queued'];
}

function truncate(str, length) {
    if (!str) return '-';
    return str.length > length ? str.substring(0, length - 3) + '...' : str;
}

// Start auto-refresh when page loads
document.addEventListener('DOMContentLoaded', function() {
    autoRefreshInterval = setInterval(refreshLogs, 5000);
    jobsRefreshInterval = setInterval(refreshJobs, 3000);
});

// Stop auto-refresh when page is hidden (battery saving)
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        if (jobsRefreshInterval) clearInterval(jobsRefreshInterval);
    } else {
        autoRefreshInterval = setInterval(refreshLogs, 5000);
        jobsRefreshInterval = setInterval(refreshJobs, 3000);
    }
});
</script>
@endpush
@endsection
