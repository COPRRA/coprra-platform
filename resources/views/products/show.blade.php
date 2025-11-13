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
                        <a href="{{ route('products.price-comparison', $product->slug) }}" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition" aria-label="{{ __('Compare All Prices for') }} {{ $product->name }}">
                            <i class="fas fa-dollar-sign mr-2" aria-hidden="true"></i>{{ __('Compare All Prices') }}
                        </a>
                        <a href="{{ route('stores.index') }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition" aria-label="{{ __('messages.view_stores_selling') }} {{ $product->name }}">
                            <i class="fas fa-shopping-cart mr-2" aria-hidden="true"></i>{{ __('messages.view_stores') }}
                        </a>
                        @auth
                        <a href="{{ route('price-alerts.create', ['product_id' => $product->id]) }}" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-3 px-6 rounded-lg text-center transition" aria-label="{{ __('Set Price Alert for') }} {{ $product->name }}">
                            <i class="fas fa-bell mr-2" aria-hidden="true"></i>{{ __('Set Price Alert') }}
                        </a>
                        @endauth
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

        <!-- Customer Reviews Section -->
        <div class="mt-12 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('messages.reviews') }}</h2>
                @auth
                <a href="{{ route('reviews.create', $product->id) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <i class="fas fa-edit mr-2" aria-hidden="true"></i>{{ __('messages.write_review') }}
                </a>
                @else
                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-semibold rounded-lg transition">
                    <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i>{{ __('Login to Write Review') }}
                </a>
                @endauth
            </div>

            <!-- Rating Summary -->
            <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($averageRating ?? 0, 1) }}</div>
                        <div class="flex items-center justify-center gap-1 mt-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= ($averageRating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" aria-hidden="true"></i>
                            @endfor
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $reviewsCount ?? 0 }} {{ __('messages.reviews') }}</div>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            @if(isset($reviews) && $reviews->count() > 0)
                <div class="space-y-6">
                    @foreach($reviews as $review)
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-b-0 last:pb-0">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $review->user->name ?? __('Anonymous') }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="flex items-center gap-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }} text-sm" aria-hidden="true"></i>
                                            @endfor
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                @auth
                                @if(auth()->id() === $review->user_id)
                                <div class="flex gap-2">
                                    <a href="{{ route('reviews.edit', $review->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>
                                    <form method="POST" action="{{ route('reviews.destroy', $review->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm" onclick="return confirm('{{ __('Are you sure you want to delete this review?') }}')">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                                @endif
                                @endauth
                            </div>
                            @if($review->title)
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $review->title }}</h3>
                            @endif
                            <p class="text-gray-700 dark:text-gray-300">{{ $review->content }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-comments fa-3x mb-4 opacity-50" aria-hidden="true"></i>
                    <p>{{ __('No reviews yet. Be the first to review this product!') }}</p>
                </div>
            @endif
        </div>

        @if(isset($relatedProducts) && $relatedProducts->count() > 0)
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('messages.related_products') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $rp)
                        <article class="product-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
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
