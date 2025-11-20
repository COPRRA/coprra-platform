@extends('layouts.app')

@section('title', __('messages.compare_page_title') . ' - ' . config('app.name'))
@section('description', __('messages.compare_page_description'))

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
            <!-- AI Analysis Section -->
            <div id="ai-analysis-container" class="mb-6 hidden">
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border border-purple-200 dark:border-purple-800 rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-robot text-purple-600 dark:text-purple-400"></i>
                            {{ __('AI-Powered Analysis') }}
                        </h2>
                        <button type="button" id="ai-analysis-close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Pros & Cons Section -->
                    <div id="ai-pros-cons" class="mb-6 hidden">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Pros & Cons') }}</h3>
                        <div id="ai-pros-cons-content" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Dynamic content will be inserted here -->
                        </div>
                    </div>
                    
                    <!-- Smart Verdict Section -->
                    <div id="ai-verdict" class="hidden">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('Smart Verdict') }}</h3>
                        <div id="ai-verdict-content" class="bg-white dark:bg-gray-800 rounded-lg p-4 border-l-4 border-purple-600">
                            <!-- Dynamic content will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>

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
                        <!-- AI Analysis Button -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20">
                            <button type="button" 
                                    id="generate-ai-analysis-btn"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg"
                                    aria-label="{{ __('messages.generate_ai_powered_analysis_comparing_selected_products') }}">
                                <i class="fas fa-robot" aria-hidden="true"></i>
                                <span>{{ __('messages.generate_ai_analysis') }}</span>
                            </button>
                            <div id="ai-analysis-loading" class="hidden mt-4 text-center">
                                <div class="inline-flex items-center gap-3 text-purple-600 dark:text-purple-400">
                                    <i class="fas fa-spinner fa-spin text-2xl"></i>
                                    <span class="text-lg font-medium">{{ __('messages.analyzing_please_wait') }}</span>
                                </div>
                            </div>
                        </div>
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
                                                    aria-label="{{ __('messages.remove') }} {{ $product->name }} {{ __('messages.from_comparison') }}"
                                                >
                                                    <i class="fas fa-times mr-1" aria-hidden="true"></i> {{ __('messages.remove') }}
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
                                                <a href="{{ route('products.offers', $product->slug) }}"
                                                   class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition">
                                                    <i class="fas fa-shopping-cart mr-2"></i> {{ __('Buy Now') }}
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

@push('scripts')
<script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
(function() {
    const analyzeBtn = document.getElementById('generate-ai-analysis-btn');
    const loadingDiv = document.getElementById('ai-analysis-loading');
    const analysisContainer = document.getElementById('ai-analysis-container');
    const prosConsDiv = document.getElementById('ai-pros-cons');
    const prosConsContent = document.getElementById('ai-pros-cons-content');
    const verdictDiv = document.getElementById('ai-verdict');
    const verdictContent = document.getElementById('ai-verdict-content');
    const closeBtn = document.getElementById('ai-analysis-close');

    if (!analyzeBtn) return;

    // Get product IDs from the comparison table
    function getProductIds() {
        const productIds = [];
        const table = document.querySelector('table');
        if (!table) return [];

        // Extract product IDs from remove buttons
        const removeButtons = table.querySelectorAll('[data-compare-remove]');
        removeButtons.forEach(btn => {
            const productId = parseInt(btn.getAttribute('data-compare-remove'), 10);
            if (productId && !isNaN(productId)) {
                productIds.push(productId);
            }
        });

        return productIds;
    }

    // Generate AI Analysis
    analyzeBtn.addEventListener('click', async function() {
        const productIds = getProductIds();

        if (productIds.length < 2) {
            alert('{{ __("Please add at least 2 products to compare.") }}');
            return;
        }

        // Show loading state
        analyzeBtn.disabled = true;
        analyzeBtn.classList.add('opacity-50', 'cursor-not-allowed');
        loadingDiv.classList.remove('hidden');
        analysisContainer.classList.add('hidden');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const response = await fetch('/api/compare/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    product_ids: productIds,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to generate AI analysis');
            }

            // Display results
            displayAnalysisResults(data.data);

        } catch (error) {
            console.error('AI Analysis Error:', error);
            alert('{{ __("Failed to generate AI analysis. Please try again later.") }}');
        } finally {
            // Hide loading state
            analyzeBtn.disabled = false;
            analyzeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            loadingDiv.classList.add('hidden');
        }
    });

    // Display AI Analysis Results
    function displayAnalysisResults(analysis) {
        if (!analysis.pros_and_cons || !analysis.smart_verdict) {
            console.error('Invalid analysis data structure');
            return;
        }

        // Clear previous content
        prosConsContent.innerHTML = '';
        verdictContent.innerHTML = '';

        // Display Pros & Cons
        const prosAndCons = analysis.pros_and_cons;
        Object.keys(prosAndCons).forEach(productName => {
            const items = prosAndCons[productName];
            if (!Array.isArray(items)) return;

            const productCard = document.createElement('div');
            productCard.className = 'bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700';

            const productTitle = document.createElement('h4');
            productTitle.className = 'font-semibold text-gray-900 dark:text-white mb-3 text-lg';
            productTitle.textContent = productName;
            productCard.appendChild(productTitle);

            const prosList = document.createElement('div');
            prosList.className = 'mb-3';
            const prosTitle = document.createElement('div');
            prosTitle.className = 'text-sm font-medium text-green-700 dark:text-green-400 mb-2';
            prosTitle.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Pros:';
            prosList.appendChild(prosTitle);

            const prosUl = document.createElement('ul');
            prosUl.className = 'list-disc list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300 ml-4';

            const consList = document.createElement('div');
            const consTitle = document.createElement('div');
            consTitle.className = 'text-sm font-medium text-red-700 dark:text-red-400 mb-2';
            consTitle.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Cons:';
            consList.appendChild(consTitle);

            const consUl = document.createElement('ul');
            consUl.className = 'list-disc list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300 ml-4';

            items.forEach(item => {
                const itemText = String(item).trim();
                const isPro = itemText.toLowerCase().includes('pro') || 
                             itemText.toLowerCase().startsWith('+') ||
                             (!itemText.toLowerCase().includes('con') && !itemText.toLowerCase().startsWith('-'));

                const li = document.createElement('li');
                li.textContent = itemText.replace(/^(pro|con|pros|cons):\s*/i, '').trim();
                
                if (isPro) {
                    prosUl.appendChild(li);
                } else {
                    consUl.appendChild(li);
                }
            });

            prosList.appendChild(prosUl);
            consList.appendChild(consUl);
            productCard.appendChild(prosList);
            productCard.appendChild(consList);
            prosConsContent.appendChild(productCard);
        });

        prosConsDiv.classList.remove('hidden');

        // Display Smart Verdict
        verdictContent.innerHTML = `<p class="text-gray-700 dark:text-gray-300 leading-relaxed">${analysis.smart_verdict}</p>`;
        verdictDiv.classList.remove('hidden');

        // Show analysis container and scroll to it
        analysisContainer.classList.remove('hidden');
        analysisContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Close AI Analysis
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            analysisContainer.classList.add('hidden');
        });
    }
})();
</script>
@endpush
@endsection
