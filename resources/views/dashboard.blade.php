<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BookHub</title>
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
                <a href="/dashboard" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
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
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Dashboard</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Welcome back, {{ Auth::user()->name }}</p>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="relative hidden md:block">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-sm"></i>
                        <input type="text" placeholder="Quick search..." class="bg-gray-50 border border-gray-200 text-sm rounded-lg pl-10 pr-4 py-2.5 w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button class="relative p-2 text-gray-400 hover:text-gray-600">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </header>

            <div class="p-8">
                
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="bg-[#E0E7FF] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-indigo-600 font-medium text-sm mb-1">Total Books</p>
                                <h3 class="text-3xl font-bold text-gray-900">{{ App\Models\Book::count() }}</h3>
                                <p class="text-xs text-gray-600 mt-2">In collection</p>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-indigo-600">
                                <i class="fa-solid fa-book text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#DCFCE7] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-green-700 font-medium text-sm mb-1">Available</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ App\Models\Book::where('status', 'Available')->count() }}
                                </h3>
                                <p class="text-xs text-green-600 font-semibold mt-2 flex items-center">
                                    <i class="fa-solid fa-circle-check mr-1"></i> Ready to borrow
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-green-600">
                                <i class="fa-solid fa-check text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#FEF3C7] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-amber-700 font-medium text-sm mb-1">Borrowed</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ App\Models\Book::where('status', 'Borrowed')->count() }}
                                </h3>
                                <p class="text-xs text-amber-600 font-semibold mt-2 flex items-center">
                                    <i class="fa-solid fa-clock mr-1"></i> Currently out
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-amber-600">
                                <i class="fa-solid fa-hand-holding text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl relative overflow-hidden border border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 font-medium text-sm mb-1">Total Users</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ App\Models\User::count() }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-2">Registered members</p>
                            </div>
                            <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-600">
                                <i class="fa-solid fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Recent Activity -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900">Recent Borrowing Activity</h3>
                            <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">View All</a>
                        </div>

                        <div class="space-y-4">
                            @php
                                $recentBorrowedBooks = App\Models\Book::where('status', 'Borrowed')
                                    ->latest('updated_at')
                                    ->take(5)
                                    ->get();
                            @endphp

                            @forelse($recentBorrowedBooks as $book)
                            <div class="flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                                <div class="w-12 h-12 rounded-lg bg-indigo-50 flex-shrink-0 flex items-center justify-center">
                                    <i class="fa-solid fa-arrow-right text-indigo-600"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-semibold text-sm text-gray-900">{{ $book->title }}</p>
                                            <p class="text-xs text-gray-500 mt-1">by {{ $book->author }}</p>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ $book->updated_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            <i class="fa-solid fa-clock mr-1"></i> Borrowed
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-8 text-gray-500">
                                <i class="fa-solid fa-history text-3xl text-gray-300 mb-3 block"></i>
                                <p class="text-sm">No recent activity</p>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="space-y-6">
                        
                        <!-- Book Categories -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Book Categories</h3>
                            <div class="space-y-3">
                                @php
                                    $categories = ['Fiction', 'Non-Fiction', 'Science', 'History', 'Technology'];
                                    $colors = ['indigo', 'purple', 'green', 'amber', 'blue'];
                                @endphp

                                @foreach($categories as $index => $category)
                                    @php
                                        $count = App\Models\Book::where('category', $category)->count();
                                        $color = $colors[$index % count($colors)];
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-2 h-2 rounded-full bg-{{ $color }}-500"></div>
                                            <span class="text-sm font-medium text-gray-700">{{ $category }}</span>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                            <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <a href="{{ route('books.create') }}" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors backdrop-blur-sm">
                                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-plus text-sm"></i>
                                    </div>
                                    <span class="text-sm font-medium">Add New Book</span>
                                </a>
                                <a href="{{ route('users.create') }}" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors backdrop-blur-sm">
                                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-user-plus text-sm"></i>
                                    </div>
                                    <span class="text-sm font-medium">Add New User</span>
                                </a>
                                <a href="#" class="flex items-center gap-3 p-3 bg-white/10 hover:bg-white/20 rounded-xl transition-colors backdrop-blur-sm">
                                    <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-file-export text-sm"></i>
                                    </div>
                                    <span class="text-sm font-medium">Export Report</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
