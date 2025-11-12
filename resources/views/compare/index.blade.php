@extends('layouts.app')

@section('title', 'Compare Products - ' . config('app.name'))
@section('description', 'Compare up to 4 products side by side with detailed specifications')

@section('content')
<div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ __('Interactive Product Comparison') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    {{ __('Select up to :count products to compare every detail side by side.', ['count' => $maxProducts]) }}
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white transition">
                    <i class="fas fa-search mr-2"></i> {{ __('Browse Products') }}
                </a>
                @if($products->isNotEmpty())
                    <form method="POST" action="{{ route('compare.clear') }}" data-compare-clear-form="true">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white transition"
                                data-compare-clear="true">
                            <i class="fas fa-trash mr-2"></i> {{ __('Clear Comparison') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if($products->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center">
                <i class="fas fa-balance-scale fa-5x text-gray-300 dark:text-gray-600 mb-6"></i>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                    {{ __('Your comparison list is empty.') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ __('Add products to compare them side-by-side and find the perfect match for you.') }}
                </p>
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center px-6 py-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition">
                    <i class="fas fa-shopping-bag mr-2"></i> {{ __('Start Shopping') }}
                </a>
            </div>
        @else
            <div class="flex flex-col xl:flex-row gap-6">
                <aside class="xl:w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm p-6 h-fit">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('Highlight specific attributes') }}
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Toggle the attributes you care about. The comparison table will update instantly.') }}
                    </p>

                    <div class="space-y-3">
                        @foreach($attributeLabels as $attributeKey => $attributeLabel)
                            <label class="flex items-center gap-3 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    data-compare-attribute-toggle="{{ $attributeKey }}"
                                    checked
                                >
                                <span class="text-sm text-gray-800 dark:text-gray-200 font-medium">
                                    {{ $attributeLabel }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </aside>

                <div class="flex-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold tracking-wider text-gray-500 dark:text-gray-400 uppercase">
                                        {{ __('Specification') }}
                                    </th>
                                    @foreach($products as $product)
                                        @php
                                            $isWishlisted = in_array($product->id, $wishlistProductIds ?? [], true);
                                        @endphp
                                        <th scope="col" class="px-6 py-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                    {{ __('Product') }} {{ $loop->iteration }}
                                                </span>
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-300 transition"
                                                    data-compare-remove="{{ $product->id }}"
                                                >
                                                    <i class="fas fa-times mr-1"></i> {{ __('Remove') }}
                                                </button>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($attributeLabels as $attributeKey => $attributeLabel)
                                    @php $isZebra = $loop->odd; @endphp
                                    <tr
                                        class="{{ $isZebra ? 'bg-gray-50 dark:bg-gray-900/50' : 'bg-white dark:bg-gray-800' }}"
                                        data-compare-attribute-row="{{ $attributeKey }}"
                                    >
                                        <td class="px-6 py-5 text-sm font-semibold text-gray-900 dark:text-white align-top">
                                            {{ $attributeLabel }}
                                        </td>
                                        @foreach($products as $product)
                                            <td class="px-6 py-5 text-sm text-gray-700 dark:text-gray-200 align-top">
                                                @switch($attributeKey)
                                                    @case('image')
                                                        <div class="flex items-center justify-center">
                                                            @if($product->image ?? $product->image_url)
                                                                <img src="{{ $product->image ?? $product->image_url }}" alt="{{ $product->name }}" class="w-36 h-36 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                                                            @else
                                                                <div class="w-36 h-36 bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center">
                                                                    <i class="fas fa-image fa-2x text-gray-400"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        @break

                                                    @case('name')
                                                        <a href="{{ route('products.show', $product->slug) }}" class="block text-base font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition">
                                                            {{ $product->name }}
                                                        </a>
                                                        @break

                                                    @case('brand')
                                                        <span>{{ $product->brand->name ?? __('N/A') }}</span>
                                                        @break

                                                    @case('price')
                                                        @if(!is_null($product->price))
                                                            <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                                                ${{ number_format((float) $product->price, 2) }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">{{ __('Not available') }}</span>
                                                        @endif
                                                        @break

                                                    @case('category')
                                                        <span>{{ $product->category->name ?? __('N/A') }}</span>
                                                        @break

                                                    @case('year')
                                                        <span>{{ $product->year_of_manufacture ?? __('N/A') }}</span>
                                                        @break

                                                    @case('colors')
                                                        @php
                                                            $colorList = $product->available_colors;
                                                            $colorText = is_array($colorList) && count($colorList) > 0
                                                                ? implode(', ', array_map('trim', $colorList))
                                                                : ($product->color_list ?? null);
                                                        @endphp
                                                        <span>{{ $colorText ?: __('N/A') }}</span>
                                                        @break

                                                    @case('description')
                                                        <p class="text-sm leading-relaxed">
                                                            {{ $product->description ? Str::limit(strip_tags((string) $product->description), 220) : __('No description available.') }}
                                                        </p>
                                                        @break

                                                    @default
                                                        <span>{{ __('N/A') }}</span>
                                                @endswitch
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-gray-900/70">
                                <tr>
                                    <td class="px-6 py-5 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('Next steps') }}
                                    </td>
                                    @foreach($products as $product)
                                        @php
                                            $isWishlisted = in_array($product->id, $wishlistProductIds ?? [], true);
                                        @endphp
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col gap-3">
                                                <a href="{{ route('products.show', $product->slug) }}"
                                                   class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
                                                    <i class="fas fa-shopping-cart mr-2"></i> {{ __('اشتري الآن') }}
                                                </a>
                                                <button type="button"
                                                        class="wishlist-toggle-btn inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg text-sm font-semibold transition {{ $isWishlisted ? 'bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20' : 'bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }}"
                                                        data-product-id="{{ $product->id }}"
                                                        data-wishlisted="{{ $isWishlisted ? 'true' : 'false' }}"
                                                        data-wishlist-label-default="{{ __('أضف لقائمة الأماني') }}"
                                                        data-wishlist-label-active="{{ __('Remove from Wishlist') }}"
                                                        data-wishlist-icon-default="fas fa-heart"
                                                        data-wishlist-icon-active="fas fa-heart-broken"
                                                        data-wishlist-class-default="bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white"
                                                        data-wishlist-class-active="bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20">
                                                    <i class="wishlist-icon {{ $isWishlisted ? 'fas fa-heart-broken' : 'fas fa-heart' }} mr-2"></i>
                                                    <span class="wishlist-label">{{ $isWishlisted ? __('Remove from Wishlist') : __('أضف لقائمة الأماني') }}</span>
                                                </button>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
