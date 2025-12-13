<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
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
<<<<<<< Updated upstream
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        <i class="fa-solid fa-user-circle mr-1"></i> Staff Panel
                    </div>
=======
                <div>
                    <h1 class="font-bold text-lg tracking-tight">BookHub</h1>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Management System</p>
                </div>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                
                <a href="{{ route('books.index') }}" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
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
                    <form method="GET" action="{{ route('books.index') }}" class="space-y-4">
                        
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Search Keyword</label>
                                <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Title, Author, ISBN..." 
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Status</label>
                                <select name="status" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                    <option value="">All</option>
                                    @foreach(['Available','Borrowed','Lost','Damaged'] as $s)
                                        <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Category</label>
                                <select name="category" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Year From</label>
                                <input type="number" name="year_from" value="{{ $filters['year_from'] ?? '' }}" placeholder="2000" min="1900" max="2099"
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Year To</label>
                                <input type="number" name="year_to" value="{{ $filters['year_to'] ?? '' }}" placeholder="2025" min="1900" max="2099"
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex gap-3">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500">Sort:</label>
                                    <select name="sort" class="bg-gray-50 border-none text-sm font-medium rounded-lg py-2 px-3 focus:ring-2 focus:ring-indigo-500 text-gray-600">
                                        <option value="">Newest First</option>
                                        <option value="title" {{ ($filters['sort'] ?? '') === 'title' ? 'selected' : '' }}>Title A-Z</option>
                                        <option value="year" {{ ($filters['sort'] ?? '') === 'year' ? 'selected' : '' }}>Year (Desc)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="{{ route('books.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">
                                    <i class="fa-solid fa-rotate-right mr-1"></i> Reset
                                </a>
                                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm">
                                    <i class="fa-solid fa-search mr-1"></i> Search
                                </button>
                                <a href="{{ route('books.create') }}" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-bold shadow-sm transition-all">
                                    <i class="fa-solid fa-plus mr-1"></i> Add Book
                                </a>
                            </div>
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
                                            @if($book->cover_image)
                                                <img src="data:image/jpeg;base64,{{ base64_encode($book->cover_image) }}" 
                                                     class="w-full h-full object-cover book-cover-clickable" 
                                                     onclick="openImageModal(this.src, '{{ addslashes($book->title) }}')"
                                                     title="Click to zoom">
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

    <!-- Image Zoom Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 items-center justify-center p-4" onclick="closeImageModal()">
        <div class="relative max-w-4xl max-h-[90vh] bg-white rounded-xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
            <div class="absolute top-0 right-0 p-4 z-10">
                <button onclick="closeImageModal()" class="w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-900 mb-4">Book Cover</h3>
                <div class="flex items-center justify-center">
                    <img id="modalImage" src="" alt="Book Cover" class="max-w-full max-h-[70vh] object-contain rounded-lg shadow-lg">
>>>>>>> Stashed changes
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="sm:flex sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Books Management</h1>
                <p class="mt-1 text-sm text-gray-500">Manage library assets, track status, and audit inventory.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('books.create') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <i class="fa-solid fa-plus mr-2"></i> Add New Book
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <i class="fa-solid fa-layer-group text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Books</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ $books->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <i class="fa-solid fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Available</dt>
                                <dd class="text-lg font-bold text-gray-900">
                                    {{ $books->where('status', 'Available')->count() }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
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

        <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="relative max-w-xs w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400"></i>
                    </div>
                    <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search books...">
                </div>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Book Info
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Author & Category
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ISBN / Year
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($books as $book)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-10 bg-gray-200 rounded flex items-center justify-center overflow-hidden border border-gray-300">
                                    @if($book->cover_path)
                                        <img class="h-12 w-10 object-cover" src="{{ asset('storage/' . $book->cover_path) }}" alt="">
                                    @else
                                        <i class="fa-solid fa-book text-gray-400"></i>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $book->title }}</div>
                                    <div class="text-xs text-gray-500">ID: BK-{{ str_pad($book->bookId, 4, '0', STR_PAD_LEFT) }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $book->author }}</div>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $book->category }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex flex-col">
                                <span class="font-mono text-xs">{{ $book->isbn }}</span>
                                <span>{{ $book->year }}</span>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($book->status === 'Available')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Available
                                </span>
                            @elseif($book->status === 'Borrowed')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Borrowed
                                </span>
                            @elseif($book->status === 'Lost')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Lost
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    {{ $book->status }}
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="#" class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
                                <p class="text-lg font-medium">No books found</p>
                                <p class="text-sm">Get started by adding a new book to the inventory.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-center text-xs text-gray-400">
            Secure Library Management System &copy; 2025
        </div>

    </div>
</body>
</html>