@extends('layouts.app')

@section('title', 'Contact Us - COPRRA')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Contact Us | اتصل بنا</h1>
            <p class="text-lg text-gray-600">We'd love to hear from you! For any questions, feedback, or inquiries, please use the form below or email us directly.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg relative" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Contact Form -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-semibold mb-6">Send Us a Message</h2>
                <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Your Name</label>
                        <input type="text" id="name" name="name" required value="{{ old('name') }}" autocomplete="name"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Your Email</label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}" autocomplete="email"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                        <input type="text" id="subject" name="subject" required value="{{ old('subject') }}" autocomplete="off"
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('subject') border-red-500 @enderror">
                        @error('subject')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                        <textarea id="message" name="message" rows="5" required autocomplete="off"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition font-semibold">
                        Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-semibold mb-6">Contact Information</h2>
                <p class="text-gray-600 mb-8">For all inquiries, please contact us via email.</p>

                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">البريد الإلكتروني | Email</h3>
                    <a href="mailto:contact@coprra.com" class="text-2xl font-bold text-blue-600 hover:text-blue-800 transition">
                        contact@coprra.com
                    </a>
                </div>

                <div class="mt-8 p-6 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold mb-3">💡 Quick Tips</h3>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li>✓ We typically respond within 24 hours</li>
                        <li>✓ Check your spam folder for our replies</li>
                        <li>✓ For urgent matters, mention "URGENT" in subject</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
