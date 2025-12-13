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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="w-64 bg-[#0F172A] text-white flex-shrink-0 hidden md:flex flex-col fixed h-full z-20">
            <div class="h-20 flex items-center px-8 border-b border-gray-800">
                <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                    <i class="fa-solid fa-book-open text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg tracking-tight">BookHub</h1>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Management System</p>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-house w-6"></i>
                    <span class="font-medium text-sm">Dashboard</span>
                </a>

                <a href="{{ route('books.index') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-book w-6"></i>
                    <span class="font-medium text-sm">Books</span>
                </a>

                @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-users w-6"></i>
                    <span class="font-medium text-sm">User Management</span>
                </a>
                @endif
                
                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-chart-simple w-6"></i>
                    <span class="font-medium text-sm">Reports</span>
                </a>

                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-gear w-6"></i>
                    <span class="font-medium text-sm">Settings</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-800">
                <a href="{{ route('users.show', Auth::id()) }}" class="block">
                    <div class="bg-[#1E293B] rounded-xl p-3 flex items-center gap-3 hover:bg-[#2D3B52] transition-colors cursor-pointer">
                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400">{{ ucfirst(Auth::user()->role) }}</p>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" onclick="event.stopPropagation();">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white transition-colors">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </form>
                    </div>
                </a>
            </div>
        </aside>

        <main class="flex-1 md:ml-64 relative">
            
            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <a href="{{ route('users.index') }}" class="w-10 h-10 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-arrow-left text-gray-600"></i>
                    </a>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">User Profile</h2>
                        <p class="text-sm text-gray-500 mt-0.5">View and manage user information</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    @if($user->id === Auth::id())
                    <!-- Own Profile: Use Profile Edit Route -->
                    <a href="{{ route('profile.edit') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors">
                        <i class="fa-solid fa-pen mr-2"></i>Edit My Profile
                    </a>
                    @elseif(in_array(Auth::user()->role, ['Staff', 'Admin']))
                    <!-- Staff/Admin editing another user: Use User Management Edit -->
                    <a href="{{ route('users.edit', $user) }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors">
                        <i class="fa-solid fa-pen mr-2"></i>Edit User
                    </a>
                    @endif
                    
                    @if($user->id !== Auth::id() && in_array(Auth::user()->role, ['Staff', 'Admin']))
                    @if($user->trashed() || $user->status === 'Inactive')
                        <!-- Activate Button -->
                        <form action="{{ route('users.restore', $user->id) }}" method="POST" onsubmit="return confirm('Activate this user? They will regain access to the system.');">
                            @csrf
                            <button class="px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors">
                                <i class="fa-solid fa-check mr-2"></i>Activate User
                            </button>
                        </form>
                    @else
                        <!-- Deactivate Button -->
                        <form action="{{ route('users.deactivate', $user) }}" method="POST" onsubmit="return confirm('Deactivate this user? They will no longer be able to access the system.');">
                            @csrf @method('DELETE')
                            <button class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors">
                                <i class="fa-solid fa-ban mr-2"></i>Deactivate User
                            </button>
                        </form>
                    @endif
                    @endif
                </div>
            </header>

            <div class="p-8">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Profile Card -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                            <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center text-3xl font-bold mb-4
                                {{ $user->role === 'Admin' ? 'bg-red-100 text-red-600' : 
                                   ($user->role === 'Staff' ? 'bg-green-100 text-green-600' : 'bg-indigo-100 text-indigo-600') }}">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $user->name }}</h3>
                            
                            @if($user->role === 'Admin')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Administrator
                                </span>
                            @elseif($user->role === 'Staff')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                    <i class="fa-solid fa-user-tie mr-1"></i> Staff Member
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                    <i class="fa-solid fa-user-graduate mr-1"></i> Student
                                </span>
                            @endif

                            <div class="mt-3">
                                @if($user->trashed() || $user->status === 'Inactive')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                        <i class="fa-solid fa-circle-xmark mr-1"></i> Inactive
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                        <i class="fa-solid fa-circle-check mr-1"></i> Active
                                    </span>
                                @endif
                            </div>

                            <div class="mt-6 pt-6 border-t border-gray-100 space-y-3 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                        <i class="fa-solid fa-envelope"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Email</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $user->email }}</p>
                                    </div>
                                </div>

                                @if($user->student_id)
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                        <i class="fa-solid fa-id-card"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Student ID</p>
                                        <p class="text-sm font-medium text-gray-900 font-mono">{{ $user->student_id }}</p>
                                    </div>
                                </div>
                                @endif

                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                        <i class="fa-solid fa-calendar-plus"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Joined</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $user->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400">
                                        <i class="fa-solid fa-clock"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs text-gray-500">Last Updated</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $user->updated_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Stats -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
                            <h4 class="font-bold text-gray-900 mb-4">Activity Stats</h4>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i class="fa-solid fa-book text-xs"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Books Borrowed</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900">0</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                                            <i class="fa-solid fa-rotate-left text-xs"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Books Returned</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900">0</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                                            <i class="fa-solid fa-clock text-xs"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Active Borrows</span>
                                    </div>
                                    <span class="text-lg font-bold text-gray-900">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details & Activity -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Account Information -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-bold text-gray-900">Account Information</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Full Name</label>
                                    <p class="mt-2 text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Email Address</label>
                                    <p class="mt-2 text-sm font-medium text-gray-900">{{ $user->email }}</p>
                                </div>

                                @if($user->student_id)
                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Student ID</label>
                                    <p class="mt-2 text-sm font-medium text-gray-900 font-mono">{{ $user->student_id }}</p>
                                </div>
                                @endif

                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</label>
                                    <p class="mt-2 text-sm font-medium text-gray-900">{{ $user->role }}</p>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">User ID</label>
                                    <p class="mt-2 text-sm font-medium text-gray-900 font-mono">#{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</p>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Account Status</label>
                                    <p class="mt-2">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Active
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Borrowing History -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-bold text-gray-900">Borrowing History</h3>
                                <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View All</a>
                            </div>

                            <div class="text-center py-12 text-gray-500">
                                <i class="fa-solid fa-book-open text-4xl text-gray-300 mb-4 block"></i>
                                <p class="text-lg font-medium">No borrowing history</p>
                                <p class="text-sm mt-1">This user hasn't borrowed any books yet</p>
                            </div>
                        </div>

                        <!-- Recent Activity Timeline -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-6">Recent Activity</h3>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center">
                                            <i class="fa-solid fa-user-check text-green-600 text-sm"></i>
                                        </div>
                                        <div class="w-0.5 h-full bg-gray-200 mt-2"></div>
                                    </div>
                                    <div class="flex-1 pb-8">
                                        <p class="text-sm font-semibold text-gray-900">Account Created</p>
                                        <p class="text-xs text-gray-500 mt-1">User registered in the system</p>
                                        <p class="text-xs text-gray-400 mt-2">{{ $user->created_at->format('F j, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>

                                @if($user->updated_at != $user->created_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                                            <i class="fa-solid fa-pen text-blue-600 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900">Profile Updated</p>
                                        <p class="text-xs text-gray-500 mt-1">User information was modified</p>
                                        <p class="text-xs text-gray-400 mt-2">{{ $user->updated_at->format('F j, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
