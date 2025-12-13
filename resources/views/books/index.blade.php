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
        
        .book-cover-clickable { cursor: pointer; transition: transform 0.2s; }
        .book-cover-clickable:hover { transform: scale(1.1); }
        
        #imageModal { display: none; }
        #imageModal.active { display: flex; }
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
                </div>
            </div>
        </div>
    </div>

    <script>
        function openImageModal(imageSrc, bookTitle) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            
            modalImg.src = imageSrc;
            modalTitle.textContent = bookTitle;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>

</body>
</html>