<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        /* Custom scrollbar for sidebar if needed */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

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
                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-border-all w-6"></i>
                    <span class="font-medium text-sm">Dashboard</span>
                </a>
                
                <a href="{{ route('books.index') }}" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
                    <i class="fa-solid fa-book w-6"></i>
                    <span class="font-medium text-sm">Books</span>
                </a>

                <a href="#" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-users w-6"></i>
                    <span class="font-medium text-sm">Members</span>
                </a>
                
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
                <div class="bg-[#1E293B] rounded-xl p-3 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold">
                        JD
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Jane Doe from ZZZ</p>
                        <p class="text-xs text-gray-400">Staff</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 md:ml-64 relative">
            
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <h2 class="text-xl font-bold text-gray-900">Book Management</h2>
                
                <div class="flex items-center gap-6">
                    <div class="relative hidden md:block">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-sm"></i>
                        <input type="text" placeholder="Search books..." class="bg-gray-50 border border-gray-200 text-sm rounded-lg pl-10 pr-4 py-2.5 w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <button class="relative p-2 text-gray-400 hover:text-gray-600">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                </div>
            </header>

            <div class="p-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="bg-[#E0E7FF] p-6 rounded-2xl relative overflow-hidden group">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-indigo-600 font-medium text-sm mb-1">Total Books</p>
                                <h3 class="text-3xl font-bold text-gray-900">{{ $books->total() }}</h3>
                                <p class="text-xs text-green-600 font-semibold mt-2 flex items-center">
                                    <i class="fa-solid fa-arrow-up mr-1"></i> 12% from last month
                                </p>
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
                                    {{ $books->getCollection()->where('status', 'Available')->count() }}
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-green-600">
                                <i class="fa-solid fa-cart-shopping text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#FEF3C7] p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-amber-700 font-medium text-sm mb-1">Borrowed</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ $books->getCollection()->where('status', 'Borrowed')->count() }}
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-white/60 rounded-xl flex items-center justify-center text-amber-600">
                                <i class="fa-solid fa-user-group text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 p-6 rounded-2xl relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 font-medium text-sm mb-1">Lost / Other</p>
                                <h3 class="text-3xl font-bold text-gray-900">
                                    {{ $books->getCollection()->whereIn('status', ['Lost', 'Damaged'])->count() }}
                                </h3>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-gray-500">
                                <i class="fa-solid fa-chart-line text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
                    <form method="GET" action="{{ route('books.index') }}" class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        <div class="flex flex-col md:flex-row gap-3 items-center w-full md:w-auto">
                            <div class="flex items-center text-gray-500 mr-2">
                                <i class="fa-solid fa-filter mr-2"></i> Filters:
                            </div>
                            
                            <div class="relative w-full md:w-48">
                                <input name="category" value="{{ $filters['category'] ?? '' }}" placeholder="All Categories" 
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <div class="relative w-full md:w-48">
                                <select name="status" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                    <option value="">All Status</option>
                                    @foreach(['Available','Borrowed','Lost','Damaged'] as $s)
                                        <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-4 top-3.5 text-xs text-gray-400 pointer-events-none"></i>
                            </div>

                            <div class="relative w-full md:w-48">
                                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title..." 
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <a href="{{ route('books.index') }}" class="text-sm font-medium text-gray-500 hover:text-indigo-600 flex items-center gap-1 px-2">
                                <i class="fa-solid fa-rotate-right text-xs"></i> Reset
                            </a>
                        </div>

                        <div class="flex gap-2 w-full md:w-auto">
                            <button type="submit" class="hidden md:inline-block px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                                Apply
                            </button>
                            <a href="{{ route('books.create') }}" class="flex-1 md:flex-none text-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow-lg shadow-indigo-200 transition-all hover:-translate-y-0.5">
                                <i class="fa-solid fa-plus mr-2"></i> Add New Book
                            </a>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Book Title</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Author</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">ISBN</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Category</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($books as $book)
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg flex-shrink-0 flex items-center justify-center text-sm font-bold text-indigo-600 bg-indigo-50 overflow-hidden">
                                            @if($book->cover_path)
                                                <img src="{{ asset('storage/' . $book->cover_path) }}" class="w-full h-full object-cover">
                                            @else
                                                {{ substr($book->title, 0, 1) }}
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm">{{ $book->title }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $book->year }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-sm font-medium text-gray-600">
                                    {{ $book->author }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-500 font-mono">
                                    {{ $book->isbn }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600">
                                        {{ $book->category }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    @if($book->status === 'Available')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                                            Available
                                        </span>
                                    @elseif($book->status === 'Borrowed')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                            Borrowed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                            {{ $book->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('books.edit', $book) }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                        <form action="{{ route('books.destroy', $book) }}" method="POST" onsubmit="return confirm('Delete?');">
                                            @csrf @method('DELETE')
                                            <button class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-500">
                                    No books found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $books->links() }}
                </div>
            </div>
        </main>
    </div>

</body>
</html>