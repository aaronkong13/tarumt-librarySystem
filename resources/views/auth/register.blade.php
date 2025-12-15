<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="text-center">
                <i class="fa-solid fa-book-open text-blue-600 text-5xl mb-4"></i>
                <h2 class="text-3xl font-bold text-gray-900">Library Management System</h2>
                <p class="mt-2 text-sm text-gray-600">Create your student account</p>
            </div>

            @if($errors->any())
                <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-circle-xmark text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            @foreach($errors->all() as $error)
                                <p class="text-sm text-red-800">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white py-8 px-6 shadow rounded-lg">
                <form method="POST" action="{{ route('register') }}" class="space-y-6" enctype="multipart/form-data">
                    @csrf

                    <!-- Profile Image Upload -->
                    <div class="flex flex-col items-center mb-6">
                        <div class="mb-4">
                            <div id="profilePreview" class="w-24 h-24 rounded-full bg-blue-500 flex items-center justify-center text-white text-3xl font-bold border-4 border-blue-600">
                                <i class="fas fa-user text-4xl text-white"></i>
                            </div>
                        </div>
                        <div class="text-center">
                            <label for="profile_image" class="cursor-pointer inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fa-solid fa-camera mr-2"></i>
                                Choose Photo
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewImage(event)">
                            <p class="text-xs text-gray-500 mt-2">Optional - JPG, PNG or GIF (Max 2MB, auto-compressed)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password *</label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="text" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" 
                                  name="address" 
                                  rows="3"
                                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            <i class="fa-solid fa-user-plus mr-2"></i>
                            Create Account
                        </button>
                    </div>

                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Already have an account?</span>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('login') }}" 
                               class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                <i class="fa-solid fa-right-to-bracket mr-2"></i>
                                Sign in instead
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="text-center text-xs text-gray-400">
                Secure Library Management System &copy; 2025
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('profilePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="w-24 h-24 rounded-full object-cover border-4 border-blue-500">`;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>