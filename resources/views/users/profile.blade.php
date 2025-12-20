<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BookHub</title>
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
        @include('layouts.sidebar', ['active' => 'profile'])

        <main class="flex-1 md:ml-64 relative">
            
            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center space-x-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">My Profile</h2>
                        <p class="text-sm text-gray-500">View and manage your personal information</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('profile.edit') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Profile
                    </a>
                </div>
            </header>

    <div class="p-8">

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
            <div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-indigo-600 to-purple-600">
                <div class="flex items-center">
                    @if($user->profile_image)
                        <img src="data:image/jpeg;base64,{{ base64_encode($user->profile_image) }}" 
                             alt="{{ $user->name }}" 
                             class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-xl">
                    @else
                        <div class="w-32 h-32 rounded-full bg-white flex items-center justify-center text-indigo-600 text-4xl font-bold border-4 border-white shadow-xl">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="ml-6 text-white">
                        <h3 class="text-2xl leading-6 font-bold">{{ $user->name }}</h3>
                        <p class="mt-2 max-w-2xl text-sm text-indigo-100">
                            @if($user->role === 'Admin')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Administrator
                                </span>
                            @elseif($user->role === 'Staff')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <i class="fa-solid fa-user-tie mr-1"></i> Staff Member
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <i class="fa-solid fa-user-graduate mr-1"></i> Student
                                </span>
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-indigo-100">
                            <i class="fa-solid fa-calendar-plus mr-2"></i>
                            Member since {{ $user->created_at->format('F Y') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                            <i class="fa-solid fa-envelope mr-2 text-indigo-600"></i>Email Address
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-medium">
                            {{ $user->email }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                            <i class="fa-solid fa-phone mr-2 text-indigo-600"></i>Phone Number
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-medium">
                            {{ $user->phone ?? 'Not provided' }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                            <i class="fa-solid fa-location-dot mr-2 text-indigo-600"></i>Address
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->address ?? 'Not provided' }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                            <i class="fa-solid fa-user-shield mr-2 text-indigo-600"></i>Account Role
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="font-semibold">{{ $user->role }}</span>
                            @if($user->role === 'Admin')
                                <span class="ml-2 text-xs text-gray-500">Full system access</span>
                            @elseif($user->role === 'Staff')
                                <span class="ml-2 text-xs text-gray-500">Manage books and users</span>
                            @else
                                <span class="ml-2 text-xs text-gray-500">Browse and borrow books</span>
                            @endif
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                            <i class="fa-solid fa-clock mr-2 text-indigo-600"></i>Last Updated
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $user->updated_at->format('F j, Y \a\t g:i A') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Additional Info Cards (Student Only) -->
        @if($user->isStudent())
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Account Status Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-green-500 p-3">
                                <i class="fa-solid fa-circle-check text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Account Status
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        Active
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books Borrowed Card (placeholder) -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-indigo-500 p-3">
                                <i class="fa-solid fa-book text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Books Borrowed
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        0
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-600">
                                        active
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservations Card (placeholder) -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-purple-500 p-3">
                                <i class="fa-solid fa-bookmark text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Reservations
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        0
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-600">
                                        pending
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Borrowing History Section (Student Only) -->
        @if($user->isStudent() && isset($borrowingHistory) && $borrowingHistory->count() > 0)
        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-gradient-to-r from-blue-600 to-cyan-600">
                <h3 class="text-lg leading-6 font-bold text-white flex items-center">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                    My Borrowing History
                </h3>
                <p class="mt-1 text-sm text-blue-100">Your book borrowing records</p>
            </div>
            <div class="border-t border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrow Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($borrowingHistory as $borrowing)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($borrowing->book->cover_image)
                                            <img src="data:image/jpeg;base64,{{ base64_encode($borrowing->book->cover_image) }}" 
                                                 alt="{{ $borrowing->book->title }}" 
                                                 class="w-10 h-14 object-cover rounded shadow-sm mr-3">
                                        @else
                                            <div class="w-10 h-14 bg-gray-200 rounded flex items-center justify-center mr-3">
                                                <i class="fa-solid fa-book text-gray-400"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $borrowing->book->title }}</div>
                                            <div class="text-sm text-gray-500">{{ $borrowing->book->author }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $borrowing->borrow_date->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $borrowing->due_date->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $borrowing->return_date ? $borrowing->return_date->format('M j, Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($borrowing->status === 'returned')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Returned
                                        </span>
                                    @elseif($borrowing->status === 'borrowed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fa-solid fa-book-open mr-1"></i> Borrowed
                                        </span>
                                    @elseif($borrowing->status === 'overdue')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            <i class="fa-solid fa-clock mr-1"></i> Overdue
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @elseif($user->isStudent() && (!isset($borrowingHistory) || $borrowingHistory->count() === 0))
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
            <i class="fa-solid fa-book-open text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-600 font-medium">No borrowing history yet</p>
            <p class="text-sm text-gray-500 mt-1">Start exploring our book catalog!</p>
        </div>
        @endif

        <div class="mt-6 text-center text-xs text-gray-400">
            Secure Library Management System &copy; 2025
        </div>

    </div>

        </main>

    </div>
</body>
</html>
