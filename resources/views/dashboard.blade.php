<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library System</title>
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
                    <a href="/dashboard" class="text-blue-600 font-medium text-sm">Dashboard</a>
                    <a href="{{ route('books.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">Books</a>
                    @if(Auth::user()->isStaff() || Auth::user()->isAdmin())
                        <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-900 text-sm">User Management</a>
                    @endif
                    <a href="{{ route('users.show', Auth::id()) }}" class="text-gray-600 hover:text-gray-900 text-sm">
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
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, {{ Auth::user()->name }}!</h1>
            <p class="mt-2 text-gray-600">You are logged in as 
                @if(Auth::user()->isAdmin())
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                    </span>
                @elseif(Auth::user()->isStaff())
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fa-solid fa-user-tie mr-1"></i> Staff
                    </span>
                @else
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        <i class="fa-solid fa-user-graduate mr-1"></i> Student
                    </span>
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-8">
            <!-- My Profile Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <i class="fa-solid fa-user text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900">My Profile</h3>
                            <p class="mt-1 text-sm text-gray-500">View and manage your profile information</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('users.show', Auth::id()) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 w-full justify-center">
                            View Profile <i class="fa-solid fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Books Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                            <i class="fa-solid fa-book text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Books</h3>
                            <p class="mt-1 text-sm text-gray-500">Browse and manage library books</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('books.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 w-full justify-center">
                            View Books <i class="fa-solid fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            @if(Auth::user()->isStaff() || Auth::user()->isAdmin())
                <!-- User Management Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <i class="fa-solid fa-users text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <h3 class="text-lg font-medium text-gray-900">User Management</h3>
                                <p class="mt-1 text-sm text-gray-500">Manage system users and roles</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('users.index') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 w-full justify-center">
                                Manage Users <i class="fa-solid fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if(Auth::user()->isAdmin())
            <!-- Admin Stats -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                <i class="fa-solid fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                    <dd class="text-lg font-bold text-gray-900">{{ App\Models\User::count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                                <i class="fa-solid fa-book text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Books</dt>
                                    <dd class="text-lg font-bold text-gray-900">{{ App\Models\Book::count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <i class="fa-solid fa-user-graduate text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Students</dt>
                                    <dd class="text-lg font-bold text-gray-900">{{ App\Models\User::where('role', 'Student')->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                <i class="fa-solid fa-user-tie text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Staff</dt>
                                    <dd class="text-lg font-bold text-gray-900">{{ App\Models\User::where('role', 'Staff')->count() }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-4 text-center text-xs text-gray-400">
            Secure Library Management System &copy; 2025
        </div>

    </div>
</body>
</html>