@extends('layouts.app')

@section('title', __('Edit Review') . ' - ' . $review->product->name)

@section('content')
<div class="py-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="{{ route('products.show', $review->product->slug) }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline mb-6">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i> {{ __('Back to Product') }}
        </a>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ __('Edit Review') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('for') }} <a href="{{ route('products.show', $review->product->slug) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $review->product->name }}</a></p>

            <form action="{{ route('reviews.update', $review->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Rating -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('messages.rating') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-2" id="rating-container">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" name="rating" value="{{ $i }}" id="rating-{{ $i }}" class="hidden" {{ $review->rating == $i ? 'checked' : '' }} required>
                            <label for="rating-{{ $i }}" class="cursor-pointer text-3xl text-gray-300 hover:text-yellow-400 transition rating-star" data-rating="{{ $i }}">
                                <i class="fas fa-star"></i>
                            </label>
                        @endfor
                    </div>
                    @error('rating')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Title -->
                <div class="mb-6">
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Review Title') }} ({{ __('Optional') }})
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title', $review->title) }}" maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Content -->
                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Your Review') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="content" id="content" rows="6" required maxlength="1000" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('content', $review->content) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Maximum 1000 characters') }}</p>
                    @error('content')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                        <i class="fas fa-save mr-2" aria-hidden="true"></i>{{ __('Update Review') }}
                    </button>
                    <a href="{{ route('products.show', $review->product->slug) }}" class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white font-semibold py-3 px-6 rounded-lg text-center transition">
                        {{ __('messages.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.rating-star');
    const ratingInputs = document.querySelectorAll('input[name="rating"]');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            updateStars(rating);
        });

        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            highlightStars(rating);
        });
    });

    document.getElementById('rating-container').addEventListener('mouseleave', function() {
        const checkedRating = document.querySelector('input[name="rating"]:checked').value;
        updateStars(parseInt(checkedRating));
    });

    function updateStars(rating) {
        stars.forEach((star, index) => {
            const starRating = parseInt(star.dataset.rating);
            if (starRating <= rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    function highlightStars(rating) {
        stars.forEach(star => {
            const starRating = parseInt(star.dataset.rating);
            if (starRating <= rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }

    // Initialize with current rating
    const currentRating = document.querySelector('input[name="rating"]:checked').value;
    updateStars(parseInt(currentRating));
});
</script>
@endpush
@endsection

