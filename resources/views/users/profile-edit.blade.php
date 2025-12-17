<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit My Profile - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        @include('layouts.sidebar', ['active' => 'users'])

        <main class="flex-1 md:ml-64 relative">
            
            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <a href="{{ route('users.show', $user) }}" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Edit My Profile</h2>
                        <p class="text-sm text-gray-500">Update your personal information</p>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-8">
                <div class="max-w-4xl mx-auto">

                    @if($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6">
                            <div class="flex">
                                <i class="fa-solid fa-circle-exclamation text-red-500 mt-1 mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-red-800 mb-2">Please fix the following errors:</h3>
                                    <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-6">
                            <div class="flex items-center">
                                <i class="fa-solid fa-circle-check text-green-500 mr-3"></i>
                                <p class="text-green-800 font-medium">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        
                        <!-- Form Header -->
                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-8 py-6">
                            <h3 class="text-xl font-bold text-white">Personal Information</h3>
                            <p class="text-indigo-100 text-sm mt-1">Update your personal profile information</p>
                        </div>

                        <!-- Form Body -->
                        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="p-8">
                            @csrf
                            @method('PUT')

                            <!-- Profile Image Upload -->
                            <div class="mb-8 flex flex-col items-center">
                                <div class="mb-4">
                                    @if($user->profile_image)
                                        <img id="profilePreview" src="data:image/jpeg;base64,{{ base64_encode($user->profile_image) }}" 
                                             alt="Profile" class="w-32 h-32 rounded-full object-cover border-4 border-indigo-500">
                                    @else
                                        <div id="profilePreview" class="w-32 h-32 rounded-full bg-indigo-500 flex items-center justify-center text-white text-4xl font-bold border-4 border-indigo-600">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-center">
                                    <label for="profile_image" class="cursor-pointer inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fa-solid fa-camera mr-2"></i>
                                        Change Photo
                                    </label>
                                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(event)">
                                    <p class="text-xs text-gray-500 mt-2">JPG, PNG or GIF (Max 2MB, auto-compressed)</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors text-gray-800"
                                        required>
                                </div>

                                <!-- Email (Read-only) -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <input type="email" value="{{ $user->email }}" 
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed"
                                        readonly>
                                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                                </div>

                                <!-- Role (Read-only) -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Role
                                    </label>
                                    <input type="text" value="{{ ucfirst($user->role) }}" 
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed"
                                        readonly>
                                    <p class="text-xs text-gray-500 mt-1">Role cannot be changed</p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors text-gray-800"
                                        placeholder="e.g., +60123456789">
                                </div>

                                <!-- Address (Full Width) -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Address
                                    </label>
                                    <textarea name="address" rows="3" 
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors text-gray-800"
                                        placeholder="Enter your full address">{{ old('address', $user->address) }}</textarea>
                                </div>

                            </div>

                            <!-- Change Password Section -->
                            <div class="border-t border-gray-200 mt-8 pt-8">
                                <h4 class="text-lg font-bold text-gray-900 mb-4">Change Password</h4>
                                <p class="text-sm text-gray-600 mb-6">Leave blank if you don't want to change your password</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    
                                    <!-- New Password -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            New Password
                                        </label>
                                        <input type="password" name="password" 
                                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors text-gray-800"
                                            placeholder="Enter new password">
                                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Confirm New Password
                                        </label>
                                        <input type="password" name="password_confirmation" 
                                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors text-gray-800"
                                            placeholder="Confirm new password">
                                    </div>

                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                                <button type="submit" 
                                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                                    <i class="fa-solid fa-save mr-2"></i>Save Changes
                                </button>
                            </div>

                        </form>

                    </div>

                </div>
            </div>

        </main>

    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    preview.innerHTML = `<img src="${e.target.result}" alt="Profile" class="w-32 h-32 rounded-full object-cover border-4 border-indigo-500">`;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>

</body>
</html>
