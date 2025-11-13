@extends('layouts.app')

@if(isset($seoMeta))
    @section('title', $seoMeta['title'] ?? ($product->name ?? 'Product') . ' - ' . config('app.name'))
    @section('description', $seoMeta['description'] ?? ($product->description ?? 'View product details'))
@else
    @section('title', ($product->name ?? 'Product') . ' - ' . config('app.name'))
    @section('description', $product->description ?? 'View product details')
@endif

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('products.index') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline mb-6" aria-label="{{ __('messages.back_to_products_list') }}">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i> {{ __('messages.back_to_products') }}
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="grid md:grid-cols-2 gap-8 p-8">
                <!-- Product Image -->
                <div>
                    @if($product->image ?? $product->image_url)
                        <img src="{{ $product->image ?? $product->image_url }}" alt="{{ $product->name }}" class="w-full rounded-lg">
                    @else
                        <div class="w-full h-96 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image fa-6x text-gray-400"></i>
                        </div>
                    @endif
                </div>

                <!-- Product Details -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $product->name }}</h1>

                    @if(!is_null($product->price))
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-6">
                            ${{ number_format((float)$product->price, 2) }}
                        </div>
                    @endif

                    @if(!empty($product->description))
                        <div class="prose dark:prose-invert mb-6">
                            <p class="text-gray-700 dark:text-gray-300">{{ $product->description }}</p>
                        </div>
                    @endif

                    @if($product->category)
                        <div class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.category') }}:</span>
                            <a href="{{ route('categories.show', $product->category->slug) }}"
                               class="ml-2 text-blue-600 dark:text-blue-400 hover:underline"
                               aria-label="{{ __('messages.view_products_in_category') }} {{ $product->category->name }}">
                                {{ $product->category->name }}
                            </a>
                        </div>
                    @endif

                    @if($product->brand)
                        <div class="mb-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.brand') }}:</span>
                            <span class="ml-2 text-gray-900 dark:text-white font-medium">{{ $product->brand->name }}</span>
                        </div>
                    @endif

                    <!-- Product Specifications -->
                    @if($product->year_of_manufacture || $product->available_colors)
                        <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('messages.specifications') }}</h3>

                            @if($product->year_of_manufacture)
                                <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('messages.year') }}:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $product->year_of_manufacture }}</span>
                                </div>
                            @endif

                            @if($product->available_colors && is_array($product->available_colors) && count($product->available_colors) > 0)
                                <div class="flex justify-between py-2">
                                    <span class="text-gray-600 dark:text-gray-400">{{ __('messages.colors') }}:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $product->color_list }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Call to Action Buttons -->
                    <div class="mt-8 flex gap-4 flex-wrap">
                        <a href="{{ route('stores.index') }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition" aria-label="{{ __('messages.view_stores_selling') }} {{ $product->name }}">
                            <i class="fas fa-shopping-cart mr-2" aria-hidden="true"></i>{{ __('messages.view_stores') }}
                        </a>
                        <button
                            type="button"
                            class="wishlist-toggle-btn flex-1 min-w-[10rem] inline-flex items-center justify-center gap-2 font-semibold py-3 px-6 rounded-lg transition {{ ($isWishlisted ?? false) ? 'bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20' : 'bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white' }}"
                            data-product-id="{{ $product->id }}"
                            data-wishlisted="{{ ($isWishlisted ?? false) ? 'true' : 'false' }}"
                            data-wishlist-label-default="{{ __('Add to Wishlist') }}"
                            data-wishlist-label-active="{{ __('Remove from Wishlist') }}"
                            data-wishlist-icon-default="fas fa-heart"
                            data-wishlist-icon-active="fas fa-heart-broken"
                            data-wishlist-class-default="bg-gray-200 hover:bg-gray-300 text-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white"
                            data-wishlist-class-active="bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-500/10 dark:text-red-200 dark:hover:bg-red-500/20"
                            aria-label="{{ ($isWishlisted ?? false) ? __('messages.remove').' ' : '' }}{{ $product->name }} {{ __('messages.from_wishlist') }}"
                            aria-pressed="{{ ($isWishlisted ?? false) ? 'true' : 'false' }}"
                        >
                            <i class="wishlist-icon {{ ($isWishlisted ?? false) ? 'fas fa-heart-broken' : 'fas fa-heart' }} mr-2" aria-hidden="true"></i>
                            <span class="wishlist-label">
                                {{ ($isWishlisted ?? false) ? __('Remove from Wishlist') : __('Add to Wishlist') }}
                            </span>
                        </button>
                        @php
                            $alreadyCompared = in_array($product->id, session()->get('compare', []), true);
                        @endphp
                        <button
                            type="button"
                            class="flex-1 min-w-[12rem] text-center py-3 px-6 rounded-lg font-semibold transition text-white {{ $alreadyCompared ? 'bg-green-600 hover:bg-green-700' : 'bg-indigo-600 hover:bg-indigo-700' }}"
                            data-compare-add="{{ $product->id }}"
                            data-compare-added="{{ $alreadyCompared ? 'true' : 'false' }}"
                            data-compare-label-default="{{ __('Add to Compare') }}"
                            data-compare-label-added="{{ __('Added to Compare') }}"
                            data-compare-class-default="bg-indigo-600 hover:bg-indigo-700"
                            data-compare-class-added="bg-green-600 hover:bg-green-700"
                            aria-label="{{ $alreadyCompared ? __('messages.remove').' ' : '' }}{{ $product->name }} {{ __('messages.from_comparison') }}"
                            aria-pressed="{{ $alreadyCompared ? 'true' : 'false' }}"
                        >
                            <i class="fas fa-balance-scale mr-2" aria-hidden="true"></i>
                            {{ $alreadyCompared ? __('Added to Compare') : __('Add to Compare') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($relatedProducts) && $relatedProducts->count())
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('messages.related_products') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $rp)
                        <article class="product-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                            @if($rp->image ?? $rp->image_url)
                                <img src="{{ $rp->image ?? $rp->image_url }}" alt="{{ $rp->name }}" class="w-full h-48 object-cover">
                            @else
                                <div class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center" aria-hidden="true">
                                    <i class="fas fa-image fa-3x text-gray-400"></i>
                                </div>
                            @endif

                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    <a href="{{ route('products.show', $rp->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400" aria-label="{{ __('messages.view_details_for') }} {{ $rp->name }}">
                                        {{ $rp->name }}
                                    </a>
                                </h3>
                                @if(!is_null($rp->price))
                                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400" aria-label="{{ __('messages.price') }}: ${{ number_format((float)$rp->price, 2) }}">
                                        ${{ number_format((float)$rp->price, 2) }}
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Temporary inline wishlist handler until assets are properly built
(function() {
    const WISHLIST_ENDPOINT = '/api/wishlist';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const isAuthenticated = document.body.dataset.authenticated === 'true';
    const loginUrl = document.body.dataset.loginUrl ?? '/login';
    const registerUrl = document.body.dataset.registerUrl ?? '#';

    const showAuthPrompt = () => {
        // Create toast notification
        const container = document.querySelector('.coprra-toast-container') || (() => {
            const div = document.createElement('div');
            div.className = 'coprra-toast-container';
            div.style.cssText = 'position:fixed;top:1.5rem;right:1.5rem;z-index:2147483647;pointer-events:none;';
            document.body.appendChild(div);
            return div;
        })();

        const toast = document.createElement('div');
        toast.className = 'coprra-toast coprra-toast--warning';
        toast.style.cssText = 'min-width:18rem;max-width:22rem;background-color:rgba(217,119,6,0.95);color:#fff;padding:0.75rem 1rem;border-radius:0.75rem;box-shadow:0 12px 35px rgba(15,23,42,0.25);opacity:0;transform:translateY(-6px);transition:opacity 0.2s ease,transform 0.2s ease;pointer-events:auto;font-size:0.875rem;line-height:1.4;';
        
        const title = document.createElement('span');
        title.style.cssText = 'font-weight:600;margin-bottom:0.25rem;display:block;';
        title.textContent = 'Login Required';
        toast.appendChild(title);

        const message = document.createElement('span');
        message.textContent = 'Please log in or create an account to save items to your wishlist.';
        toast.appendChild(message);

        const actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:0.5rem;margin-top:0.75rem;';
        
        const loginBtn = document.createElement('a');
        loginBtn.href = loginUrl;
        loginBtn.textContent = 'Log In';
        loginBtn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;padding:0.35rem 0.75rem;border-radius:999px;background-color:rgba(255,255,255,0.18);color:#fff;font-size:0.75rem;font-weight:600;text-decoration:none;transition:background-color 0.2s ease;';
        loginBtn.onmouseover = () => loginBtn.style.backgroundColor = 'rgba(255,255,255,0.3)';
        loginBtn.onmouseout = () => loginBtn.style.backgroundColor = 'rgba(255,255,255,0.18)';
        actions.appendChild(loginBtn);

        const registerBtn = document.createElement('a');
        registerBtn.href = registerUrl;
        registerBtn.textContent = 'Register';
        registerBtn.style.cssText = loginBtn.style.cssText;
        registerBtn.onmouseover = () => registerBtn.style.backgroundColor = 'rgba(255,255,255,0.3)';
        registerBtn.onmouseout = () => registerBtn.style.backgroundColor = 'rgba(255,255,255,0.18)';
        actions.appendChild(registerBtn);

        toast.appendChild(actions);
        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    };

    const toggleWishlist = async (button) => {
        if (!button || button.disabled) return;

        if (!isAuthenticated) {
            showAuthPrompt();
            return;
        }

        const productId = button.dataset.productId;
        if (!productId) return;

        const currentlyWishlisted = button.dataset.wishlisted === 'true';
        const method = currentlyWishlisted ? 'DELETE' : 'POST';
        const endpoint = `${WISHLIST_ENDPOINT}/${productId}`;

        button.disabled = true;

        try {
            const response = await fetch(endpoint, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-XSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            if (response.status === 401 || response.status === 419) {
                showAuthPrompt();
                return;
            }

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                alert(payload?.message ?? 'Unable to update wishlist right now.');
                return;
            }

            const nextState = method !== 'DELETE';
            button.dataset.wishlisted = nextState ? 'true' : 'false';
            
            const icon = button.querySelector('.wishlist-icon');
            const label = button.querySelector('.wishlist-label');
            
            if (icon) {
                icon.className = `wishlist-icon ${nextState ? 'fas fa-heart-broken' : 'fas fa-heart'} mr-2`;
            }
            if (label) {
                label.textContent = nextState ? 'Remove from Wishlist' : 'Add to Wishlist';
            }
        } catch (error) {
            alert('An unexpected error occurred while updating your wishlist.');
        } finally {
            button.disabled = false;
        }
    };

    // Bootstrap wishlist buttons
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.wishlist-toggle-btn[data-product-id]').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                toggleWishlist(button);
            });
        });
    });
})();
</script>
@endpush
@endsection
