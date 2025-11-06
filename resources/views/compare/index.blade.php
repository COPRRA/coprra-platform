@extends('layouts.app')

@section('title', 'Compare Products - ' . config('app.name'))
@section('description', 'Compare up to 4 products side by side with detailed specifications')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Product Comparison</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Compare up to {{ $maxProducts }} products side by side</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('products.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>Browse Products
                </a>
                @if($products->count() > 0)
                    <form method="POST" action="{{ route('compare.clear') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            <i class="fas fa-trash mr-2"></i>Clear All
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($products->count() > 0)
            <!-- Filters -->
            @if($availableYears->count() > 0 || $availableColors->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Filters</h3>
                    <div class="flex gap-4 flex-wrap">
                        @if($availableYears->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Year of Manufacture</label>
                                <select id="yearFilter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">All Years</option>
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($availableColors->count() > 0)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Available Colors</label>
                                <select id="colorFilter" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                    <option value="">All Colors</option>
                                    @foreach($availableColors as $color)
                                        <option value="{{ $color }}">{{ $color }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Comparison Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Specification
                            </th>
                            @foreach($products as $product)
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Product {{ $loop->iteration }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Product Images -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Image
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4">
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-32 h-32 object-cover rounded">
                                    @else
                                        <div class="w-32 h-32 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                            <i class="fas fa-image fa-2x text-gray-400"></i>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        <!-- Product Names -->
                        <tr class="bg-gray-50 dark:bg-gray-900">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Name
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white font-semibold">
                                    <a href="{{ route('products.show', $product->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $product->name }}
                                    </a>
                                </td>
                            @endforeach
                        </tr>

                        <!-- Price -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Price
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm">
                                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                        ${{ number_format((float)$product->price, 2) }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>

                        <!-- Category -->
                        <tr class="bg-gray-50 dark:bg-gray-900">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Category
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $product->category->name ?? 'N/A' }}
                                </td>
                            @endforeach
                        </tr>

                        <!-- Brand -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Brand
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $product->brand->name ?? 'N/A' }}
                                </td>
                            @endforeach
                        </tr>

                        <!-- Year of Manufacture -->
                        <tr class="bg-gray-50 dark:bg-gray-900">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Year
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300" data-year="{{ $product->year_of_manufacture }}">
                                    {{ $product->year_of_manufacture ?? 'N/A' }}
                                </td>
                            @endforeach
                        </tr>

                        <!-- Available Colors -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Colors
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300" data-colors="{{ json_encode($product->available_colors ?? []) }}">
                                    {{ $product->color_list }}
                                </td>
                            @endforeach
                        </tr>

                        <!-- Description -->
                        <tr class="bg-gray-50 dark:bg-gray-900">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Description
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ Str::limit($product->description, 100) }}
                                </td>
                            @endforeach
                        </tr>

                        <!-- Actions -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                Actions
                            </td>
                            @foreach($products as $product)
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-2">
                                        <a href="{{ route('products.show', $product->slug) }}" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-center rounded text-sm transition">
                                            View Details
                                        </a>
                                        <form method="POST" action="{{ route('compare.remove', $product) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-12 text-center">
                <i class="fas fa-balance-scale fa-5x text-gray-300 dark:text-gray-600 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Products to Compare</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Add products from the listing pages to compare them side by side.</p>
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-shopping-bag mr-2"></i>Browse Products
                </a>
            </div>
        @endif
    </div>
</div>

@if($products->count() > 0 && ($availableYears->count() > 0 || $availableColors->count() > 0))
    <!-- Filter JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearFilter = document.getElementById('yearFilter');
            const colorFilter = document.getElementById('colorFilter');

            function applyFilters() {
                const selectedYear = yearFilter ? yearFilter.value : '';
                const selectedColor = colorFilter ? colorFilter.value : '';

                // This is a basic client-side filter
                // For a more robust solution, you'd want to implement server-side filtering
                console.log('Filter by year:', selectedYear, 'color:', selectedColor);
            }

            if (yearFilter) {
                yearFilter.addEventListener('change', applyFilters);
            }

            if (colorFilter) {
                colorFilter.addEventListener('change', applyFilters);
            }
        });
    </script>
@endif
@endsection
