<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - BookHub</title>
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
                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
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
                <h2 class="text-xl font-bold text-gray-900">User Management</h2>
                
                <div class="flex items-center gap-6">
                    <div class="relative hidden md:block">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-sm"></i>
                        <input type="text" placeholder="Search users..." class="bg-gray-50 border border-gray-200 text-sm rounded-lg pl-10 pr-4 py-2.5 w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button class="relative p-2 text-gray-400 hover:text-gray-600">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </header>

                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="bg-[#E0E7FF] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-indigo-600 font-medium text-sm mb-1">Total Users</p>
                                <h3 class="text-3xl font-bold text-gray-900">{{ $users->total() }}</h3>
                                <p class="text-xs text-green-600 font-semibold mt-2 flex items-center">
                                    <i class="fa-solid fa-arrow-up mr-1"></i> Active users
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-indigo-600">
                                <i class="fa-solid fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#F3E8FF] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-purple-700 font-medium text-sm mb-1">Students</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ App\Models\User::where('role', 'Student')->count() }}
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-purple-600">
                                <i class="fa-solid fa-user-graduate text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#DCFCE7] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-green-700 font-medium text-sm mb-1">Staff</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ App\Models\User::where('role', 'Staff')->count() }}
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-green-600">
                                <i class="fa-solid fa-user-tie text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#FEE2E2] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-red-700 font-medium text-sm mb-1">Admins</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ App\Models\User::where('role', 'Admin')->count() }}
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-red-600">
                                <i class="fa-solid fa-shield-halved text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
                    <form method="GET" action="{{ route('users.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Search</label>
                                <input name="q" value="{{ request('q') }}" placeholder="Name, Email, Student ID..." 
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Role</label>
                                <select name="role" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 text-gray-600">
                                    <option value="">All Roles</option>
                                    <option value="Student" {{ request('role') === 'Student' ? 'selected' : '' }}>Student</option>
                                    <option value="Staff" {{ request('role') === 'Staff' ? 'selected' : '' }}>Staff</option>
                                    <option value="Admin" {{ request('role') === 'Admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Status</label>
                                <select name="status" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 text-gray-600">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Sort By</label>
                                <select name="sort" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 text-gray-600">
                                    <option value="">Newest First</option>
                                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name A-Z</option>
                                    <option value="role" {{ request('sort') === 'role' ? 'selected' : '' }}>Role</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('users.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">
                                    <i class="fa-solid fa-rotate-right mr-1"></i> Reset
                                </a>
                                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm">
                                    <i class="fa-solid fa-search mr-1"></i> Search
                                </button>
                                <a href="{{ route('users.create') }}" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all">
                                    <i class="fa-solid fa-plus mr-1"></i> Add User
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">User</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Email</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Student ID</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Role</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Joined</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($users as $user)
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold 
                                            {{ $user->role === 'Admin' ? 'bg-red-100 text-red-600' : 
                                               ($user->role === 'Staff' ? 'bg-green-100 text-green-600' : 'bg-indigo-100 text-indigo-600') }}">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">ID: {{ $user->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-sm font-medium text-gray-600">
                                    {{ $user->email }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-500 font-mono">
                                    {{ $user->student_id ?? 'N/A' }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($user->role === 'Admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                                            <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                        </span>
                                    @elseif($user->role === 'Staff')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                            <i class="fa-solid fa-user-tie mr-1"></i> Staff
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            <i class="fa-solid fa-user-graduate mr-1"></i> Student
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('users.show', $user) }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('users.edit', $user) }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        @if($user->id !== Auth::id())
                                        @if($user->trashed() || $user->status === 'Inactive')
                                            <!-- Activate Button -->
                                            <form action="{{ route('users.restore', $user->id) }}" method="POST" onsubmit="return confirm('Activate this user?');">
                                                @csrf
                                                <button class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors" title="Activate User">
                                                    <i class="fa-solid fa-check text-xs"></i>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Deactivate Button -->
                                            <form action="{{ route('users.deactivate', $user) }}" method="POST" onsubmit="return confirm('Deactivate this user?');">
                                                @csrf @method('DELETE')
                                                <button class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Deactivate User">
                                                    <i class="fa-solid fa-ban text-xs"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    <i class="fa-solid fa-users text-4xl text-gray-300 mb-4 block"></i>
                                    <p class="text-lg font-medium">No users found</p>
                                    <p class="text-sm mt-1">Try adjusting your filters or search terms</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            </div>
        </main>
    </div>

</body>
</html>
