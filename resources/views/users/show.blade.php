<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - BookHub</title>
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
                <div class="flex items-center space-x-4">
                    @if(Auth::user()->isStaff() || Auth::user()->isAdmin())
                        <a href="{{ route('users.index') }}" 
                           class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition-all">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    @endif
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">User Profile</h2>
                        <p class="text-sm text-gray-500">View and manage account information</p>
                    </div>
                </div>
                <div>
                @if(Auth::id() === $user->id)
                    <a href="{{ route('profile.edit') }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit My Profile
                    </a>
                @elseif(app(\App\Services\AccessControlService::class)->canEditUser($user))
                    <a href="{{ route('users.edit', $user) }}" 
                       class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit User
                    </a>
                @endif
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
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <div class="flex items-center">
                    @if($user->profile_image)
                        <img src="data:image/jpeg;base64,{{ base64_encode($user->profile_image) }}" 
                             alt="{{ $user->name }}" 
                             class="w-32 h-32 rounded-full object-cover border-4 border-indigo-500">
                    @else
                        <div class="w-32 h-32 rounded-full bg-indigo-500 flex items-center justify-center text-white text-4xl font-bold border-4 border-indigo-600">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
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

        <!-- Borrowing History Section (Own profile or Staff/Admin viewing Student) -->
        @if((Auth::id() === $user->id || ($user->isStudent() && (Auth::user()->isStaff() || Auth::user()->isAdmin()))) && isset($borrowingHistory) && count($borrowingHistory) > 0)
        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-indigo-600">
                <h3 class="text-lg leading-6 font-bold text-white flex items-center">
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                    Borrowing History
                </h3>
                <p class="mt-1 text-sm text-indigo-100">Student's book borrowing records</p>
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
                                        @if($borrowing['book_cover_image'])
                                            <img src="data:image/jpeg;base64,{{ $borrowing['book_cover_image'] }}" 
                                                 alt="{{ $borrowing['book_title'] }}" 
                                                 class="w-10 h-14 object-cover rounded shadow-sm mr-3">
                                        @else
                                            <div class="w-10 h-14 bg-gray-200 rounded flex items-center justify-center mr-3">
                                                <i class="fa-solid fa-book text-gray-400"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $borrowing['book_title'] }}</div>
                                            <div class="text-sm text-gray-500">{{ $borrowing['book_author'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($borrowing['borrow_date'])->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($borrowing['due_date'])->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $borrowing['return_date'] ? \Carbon\Carbon::parse($borrowing['return_date'])->format('M j, Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($borrowing['status'] === 'returned')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Returned
                                        </span>
                                    @elseif($borrowing['status'] === 'borrowed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <i class="fa-solid fa-book-open mr-1"></i> Borrowed
                                        </span>
                                    @elseif($borrowing['status'] === 'overdue')
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
        @elseif((Auth::id() === $user->id || ($user->isStudent() && (Auth::user()->isStaff() || Auth::user()->isAdmin()))) && (!isset($borrowingHistory) || count($borrowingHistory) === 0))
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
            <i class="fa-solid fa-book-open text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-600 font-medium">No borrowing history</p>
            <p class="text-sm text-gray-500 mt-1">This user hasn't borrowed any books yet</p>
        </div>
        @endif

        <!-- Reservation Records Section -->
        @if((Auth::id() === $user->id || $user->isStudent()) && isset($reservations) && count($reservations) > 0)
        <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 bg-blue-600">
                <h3 class="text-lg leading-6 font-bold text-white flex items-center">
                    <i class="fa-solid fa-calendar-check mr-2"></i>
                    Reservation Records
                </h3>
                <p class="mt-1 text-sm text-blue-100">Student's book reservation records</p>
            </div>
            <div class="border-t border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue Position</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($reservations as $reservation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($reservation['book_cover_image'] ?? null)
                                            <img src="data:image/jpeg;base64,{{ $reservation['book_cover_image'] }}" 
                                                 alt="{{ $reservation['book_title'] }}" 
                                                 class="w-10 h-14 object-cover rounded shadow-sm mr-3">
                                        @else
                                            <div class="w-10 h-14 bg-gray-200 rounded flex items-center justify-center mr-3">
                                                <i class="fa-solid fa-book text-gray-400"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $reservation['book_title'] }}</div>
                                            <div class="text-sm text-gray-500">{{ $reservation['book_author'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($reservation['reservation_date'])->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $reservation['queue_position'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reservation['status'] === 'active')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            <i class="fa-solid fa-clock mr-1"></i> Active
                                        </span>
                                    @elseif($reservation['status'] === 'notified')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <i class="fa-solid fa-bell mr-1"></i> Notified
                                        </span>
                                    @elseif($reservation['status'] === 'cancelled')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            <i class="fa-solid fa-times mr-1"></i> Cancelled
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
        @elseif((Auth::id() === $user->id || $user->isStudent()) && (!isset($reservations) || count($reservations) === 0))
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
            <i class="fa-solid fa-calendar-check text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-600 font-medium">No reservation records</p>
            <p class="text-sm text-gray-500 mt-1">This user hasn't made any reservations yet</p>
        </div>
        @endif

        <div class="mt-4 text-center text-xs text-gray-400">
            Secure Library Management System &copy; 2025
        </div>

    </div>

        </main>

    </div>
</body>
</html>
