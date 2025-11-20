@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-center">Edit Profile</h2>

        @if (session('status'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="profile-update-message" class="hidden mb-4"></div>

        <form id="profile-update-form" action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>

            <div class="mb-4">
                <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Current Password (required to change password)</label>
                <input type="password" name="current_password" id="current_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-4">
                <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                <input type="password" name="new_password" id="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="mb-6">
                <label for="new_password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Profile
                </button>
                <a href="{{ route('home') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Cancel
                </a>
            </div>
        </form>

        <!-- Privacy Settings Section (GDPR Compliance) -->
        <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">{{ __('Privacy Settings') }}</h3>
            
            <div class="space-y-4">
                <!-- Export Data Button -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('Export My Data') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Download a copy of all your personal data stored on our platform, including your profile information, wishlist, price alerts, reviews, and points history.') }}
                    </p>
                    <a href="{{ route('profile.export-data') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-download mr-2"></i>
                        {{ __('Export My Data') }}
                    </a>
                </div>

                <!-- Delete Account Button -->
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('Delete My Account') }}
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('Permanently delete your account and all associated data. This action cannot be undone.') }}
                    </p>
                    <button 
                        type="button"
                        onclick="showDeleteAccountModal()"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-2"></i>
                        {{ __('Delete My Account') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Account Modal -->
        <div id="deleteAccountModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('Delete Account') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ __('Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently deleted.') }}
                </p>
                <div id="delete-account-message" class="hidden mb-4"></div>
                <form action="{{ route('profile.delete-account') }}" method="POST" id="deleteAccountForm">
                    @csrf
                    @method('DELETE')
                    <div class="mb-4">
                        <label for="delete_password" class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">
                            {{ __('Enter your password to confirm') }}
                        </label>
                        <input 
                            type="password" 
                            name="password" 
                            id="delete_password" 
                            required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline"
                        >
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="confirm_delete" 
                                value="1" 
                                required
                                class="mr-2"
                            >
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ __('I understand that this action cannot be undone') }}
                            </span>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            type="submit" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            {{ __('Delete Account') }}
                        </button>
                        <button 
                            type="button" 
                            onclick="hideDeleteAccountModal()"
                            class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
        function showDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('hidden');
        }
        function hideDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.add('hidden');
        }

        // Handle profile update form submission
        document.getElementById('profile-update-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('profile-update-message');
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.textContent = 'Updating...';
            
            // Hide previous messages
            messageDiv.classList.add('hidden');
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    messageDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4';
                    messageDiv.textContent = data.message || 'Profile updated successfully.';
                    messageDiv.classList.remove('hidden');
                    
                    // Reload page after 1.5 seconds to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    messageDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
                    messageDiv.textContent = data.message || 'Failed to update profile.';
                    messageDiv.classList.remove('hidden');
                    
                    // Show validation errors if any
                    if (data.errors) {
                        let errorHtml = '<ul class="list-disc list-inside mt-2">';
                        for (const field in data.errors) {
                            data.errors[field].forEach(error => {
                                errorHtml += `<li>${error}</li>`;
                            });
                        }
                        errorHtml += '</ul>';
                        messageDiv.innerHTML = messageDiv.textContent + errorHtml;
                    }
                }
            } catch (error) {
                // Show error message
                messageDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
                messageDiv.textContent = 'An error occurred while updating your profile. Please try again.';
                messageDiv.classList.remove('hidden');
            } finally {
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = 'Update Profile';
            }
        });

        // Handle delete account form submission
        document.getElementById('deleteAccountForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('delete-account-message');
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Ensure _method is set to DELETE
            formData.append('_method', 'DELETE');
            
            // Disable submit button and show loading state
            submitButton.disabled = true;
            submitButton.textContent = 'Deleting...';
            
            // Hide previous messages
            messageDiv.classList.add('hidden');
            
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                });
                
                if (response.ok) {
                    // Account deleted successfully - redirect to home page
                    window.location.href = '/';
                } else {
                    // Handle errors
                    let errorMessage = 'Failed to delete account. Please try again.';
                    
                    if (response.status === 422) {
                        // Validation errors
                        try {
                            const data = await response.json();
                            if (data.errors) {
                                let errorHtml = '<ul class="list-disc list-inside mt-2">';
                                for (const field in data.errors) {
                                    data.errors[field].forEach(error => {
                                        errorHtml += `<li>${error}</li>`;
                                    });
                                }
                                errorHtml += '</ul>';
                                errorMessage = errorHtml;
                            } else if (data.message) {
                                errorMessage = data.message;
                            }
                        } catch (parseError) {
                            // If JSON parsing fails, use default message
                        }
                    } else if (response.status === 419) {
                        errorMessage = 'Session expired. Please refresh the page and try again.';
                    } else if (response.status === 401) {
                        errorMessage = 'You are not authenticated. Please log in and try again.';
                    }
                    
                    // Show error message
                    messageDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
                    messageDiv.innerHTML = errorMessage;
                    messageDiv.classList.remove('hidden');
                    
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.textContent = 'Delete Account';
                }
            } catch (error) {
                // Show error message
                messageDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
                messageDiv.textContent = 'An error occurred while deleting your account. Please try again.';
                messageDiv.classList.remove('hidden');
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.textContent = 'Delete Account';
                
                console.error('Delete account error:', error);
            }
        });
        </script>
    </div>
</div>
@endsection
