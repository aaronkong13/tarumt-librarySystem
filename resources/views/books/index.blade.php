<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookHub Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-house w-6"></i>
                    <span class="font-medium text-sm">Dashboard</span>
                </a>

                <a href="{{ route('books.index') }}" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
                    <i class="fa-solid fa-book w-6"></i> <!-- i set this to selected element ah-->
                    <span class="font-medium text-sm">Books Management</span>
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
                        @if(Auth::user()->profile_image)
                            <img src="data:image/jpeg;base64,{{ base64_encode(Auth::user()->profile_image) }}" 
                                 alt="{{ Auth::user()->name }}" 
                                 class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                        @endif
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
                    <form method="GET" action="{{ route('books.index') }}" id="searchFilterForm" class="space-y-4">
                        
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            <div class="md:col-span-2">
                                <label class="text-xs text-gray-500 mb-1 block">Search Keyword</label>
                                <input name="q" id="searchInput" value="{{ $filters['q'] ?? '' }}" placeholder="Title, Author, ISBN..." 
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Status</label>
                                <div class="relative">
                                    <select name="status" id="statusFilter" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 pr-10 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                        <option value="">All</option>
                                        @foreach(['Available','Borrowed','Lost','Damaged'] as $s)
                                            <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                            
                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Category</label>
                                <div class="relative">
                                    <select name="category" id="categoryFilter" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 pr-10 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Year From</label>
                                <input type="number" name="year_from" id="yearFrom" value="{{ $filters['year_from'] ?? '' }}" placeholder="2000" min="1900" max="2099"
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>

                            <div>
                                <label class="text-xs text-gray-500 mb-1 block">Year To</label>
                                <input type="number" name="year_to" id="yearTo" value="{{ $filters['year_to'] ?? '' }}" placeholder="2025" min="1900" max="2099"
                                    class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex gap-3">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-gray-500">Sort:</label>
                                    <select name="sort" id="sortFilter" class="bg-gray-50 border-none text-sm font-medium rounded-lg py-2 px-3 focus:ring-2 focus:ring-indigo-500 text-gray-600">
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

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="booksTableContainer">
                    <div id="loadingOverlay" class="hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                        <div class="flex flex-col items-center">
                            <i class="fa-solid fa-spinner fa-spin text-4xl text-indigo-600 mb-2"></i>
                            <p class="text-sm text-gray-600">Loading...</p>
                        </div>
                    </div>
                    @include('books.partials.book-table', ['books' => $books])
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
        // AJAX Search, Filter, and Pagination
        let debounceTimer;
        const searchForm = document.getElementById('searchFilterForm');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const yearFrom = document.getElementById('yearFrom');
        const yearTo = document.getElementById('yearTo');
        const sortFilter = document.getElementById('sortFilter');
        const tableContainer = document.getElementById('booksTableContainer');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Debounced search for keyword input
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performAjaxSearch();
            }, 500); // 500ms delay
        });

        // Instant filter on dropdowns and year inputs
        [statusFilter, categoryFilter, yearFrom, yearTo, sortFilter].forEach(element => {
            element.addEventListener('change', performAjaxSearch);
        });

        // Prevent default form submit, use AJAX instead
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performAjaxSearch();
        });

        function performAjaxSearch() {
            const formData = new FormData(searchForm);
            const params = new URLSearchParams(formData);
            const url = `{{ route('books.index') }}?${params.toString()}`;

            // Show loading overlay
            loadingOverlay.classList.remove('hidden');

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                // Update table content
                tableContainer.innerHTML = html;
                
                // Hide loading overlay
                loadingOverlay.classList.add('hidden');

                // Update URL without reloading page
                window.history.pushState({}, '', url);

                // Re-attach pagination event listeners
                attachPaginationListeners();
            })
            .catch(error => {
                console.error('Error:', error);
                loadingOverlay.classList.add('hidden');
                alert('Failed to load books. Please try again.');
            });
        }

        function attachPaginationListeners() {
            // Find all pagination links
            const paginationLinks = document.querySelectorAll('#booksTableContainer .pagination a');
            
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    
                    if (!url) return;

                    // Show loading
                    loadingOverlay.classList.remove('hidden');

                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html'
                        }
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.text();
                    })
                    .then(html => {
                        tableContainer.innerHTML = html;
                        loadingOverlay.classList.add('hidden');
                        
                        // Update URL
                        window.history.pushState({}, '', url);

                        // Scroll to top of table
                        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

                        // Re-attach listeners for new pagination links
                        attachPaginationListeners();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingOverlay.classList.add('hidden');
                        alert('Failed to load page. Please try again.');
                    });
                });
            });
        }

        // Initial attachment of pagination listeners
        document.addEventListener('DOMContentLoaded', function() {
            attachPaginationListeners();
        });

        // Image Zoom Modal
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        // Show success popup message on page load
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '{{ session('success') }}',
                        confirmButtonColor: '#4f46e5',
                        confirmButtonText: 'OK',
                        timer: 5000,
                        timerProgressBar: true,
                    });
                }, 100);
            });
        @endif
    </script>

</body>
</html>