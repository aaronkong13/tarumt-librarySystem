<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fa-solid fa-book-open text-blue-600 text-2xl mr-3"></i>
                    <span class="font-bold text-xl text-gray-900">LMS Admin</span>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="/dashboard" class="text-gray-600 hover:text-gray-900 text-sm">Dashboard</a>
                    <a href="{{ route('books.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">Books</a>
                    @if(Auth::user()->isStaff() || Auth::user()->isAdmin())
                        <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">User Management</a>
                    @endif
                    <a href="{{ route('users.show', Auth::id()) }}" class="text-blue-600 font-medium text-sm">
                        <i class="fa-solid fa-user-circle mr-1"></i> {{ Auth::user()->name }}
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-900 text-sm">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">User Profile</h1>
                <p class="mt-1 text-sm text-gray-500">View and manage your account information</p>
            </div>
            @if(app(\App\Services\AccessControlService::class)->canEditUser($user))
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('users.edit', $user) }}" 
                       class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Profile
                    </a>
                </div>
            @endif
        </div>

        @if(session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6 border-l-4 border-green-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-check text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-20 w-20 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-user text-gray-500 text-3xl"></i>
                    </div>
                    <div class="ml-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $user->name }}</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            @if($user->role === 'Admin')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                </span>
                            @elseif($user->role === 'Staff')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fa-solid fa-user-tie mr-1"></i> Staff
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <i class="fa-solid fa-user-graduate mr-1"></i> Student
                                </span>
                            @endif
                            
                            @if($user->status === 'Active')
                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-hashtag mr-2"></i>User ID
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-user mr-2"></i>Full Name
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->name }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-envelope mr-2"></i>Email Address
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->email }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-phone mr-2"></i>Phone Number
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->phone ?? 'Not provided' }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-location-dot mr-2"></i>Address
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->address ?? 'Not provided' }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-calendar-plus mr-2"></i>Member Since
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->created_at->format('F j, Y') }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">
                            <i class="fa-solid fa-clock mr-2"></i>Last Updated
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->updated_at->format('F j, Y g:i A') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mt-6 flex justify-between">
            <a href="{{ url()->previous() }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back
            </a>

            @if(Auth::id() === $user->id)
                <a href="{{ route('users.edit', $user) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Edit My Profile
                </a>
            @endif
        </div>

        <div class="mt-4 text-center text-xs text-gray-400">
            Secure Library Management System &copy; 2025
        </div>

    </div>
</body>
</html>
