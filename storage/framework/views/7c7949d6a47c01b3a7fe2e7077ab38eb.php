<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Collection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .book-cover-clickable { cursor: pointer; transition: transform 0.2s; }
        .book-cover-clickable:hover { transform: scale(1.05); }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">
<div class="flex min-h-screen">
    <?php echo $__env->make('layouts.sidebar', ['active' => 'books'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="flex-1 md:ml-64 relative">
        <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Book Collection</h2>
                <p class="text-sm text-gray-500">Browse and discover titles</p>
            </div>
        </header>

        <div class="p-8">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
                <form method="GET" action="<?php echo e(route('books.catalog')); ?>" id="searchFilterForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-500 mb-1 block">Search Keyword</label>
                            <input name="q" id="searchInput" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Title, Author, ISBN..."
                                   class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Status</label>
                            <div class="relative">
                                <select name="status" id="statusFilter" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 pr-10 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                    <option value="">All</option>
                                    <?php $__currentLoopData = ['Available','Borrowed','Reserved','Lost','Damaged']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($s); ?>" <?php echo e(($filters['status'] ?? '') === $s ? 'selected' : ''); ?>><?php echo e($s); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Category</label>
                            <div class="relative">
                                <select name="category" id="categoryFilter" class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 pr-10 focus:ring-2 focus:ring-indigo-500 text-gray-600 cursor-pointer appearance-none">
                                    <option value="">All Categories</option>
                                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($cat); ?>" <?php echo e(($filters['category'] ?? '') === $cat ? 'selected' : ''); ?>><?php echo e($cat); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Year From</label>
                            <input type="number" name="year_from" id="yearFrom" value="<?php echo e($filters['year_from'] ?? ''); ?>" placeholder="2000" min="1500" max="2099"
                                   class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Year To</label>
                            <input type="number" name="year_to" id="yearTo" value="<?php echo e($filters['year_to'] ?? ''); ?>" placeholder="2025" min="1500" max="2099"
                                   class="w-full bg-gray-50 border-none text-sm font-medium rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-indigo-500 placeholder-gray-500">
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex gap-3">
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-500">Sort:</label>
                                <select name="sort" id="sortFilter" class="bg-gray-50 border-none text-sm font-medium rounded-lg py-2 px-3 focus:ring-2 focus:ring-indigo-500 text-gray-600">
                                    <option value="">Newest First</option>
                                    <option value="title" <?php echo e(($filters['sort'] ?? '') === 'title' ? 'selected' : ''); ?>>Title A-Z</option>
                                    <option value="year" <?php echo e(($filters['sort'] ?? '') === 'year' ? 'selected' : ''); ?>>Year (Desc)</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo e(route('books.catalog')); ?>" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">
                                <i class="fa-solid fa-rotate-right mr-1"></i> Reset
                            </a>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm">
                                <i class="fa-solid fa-search mr-1"></i> Search
                            </button>
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
                <?php echo $__env->make('layouts.book-cards', ['books' => $books], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </main>
</div>

<script>
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

searchInput.addEventListener('input', function(){
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(performAjaxSearch, 500);
});
[statusFilter, categoryFilter, yearFrom, yearTo, sortFilter].forEach(el => el.addEventListener('change', performAjaxSearch));
searchForm.addEventListener('submit', function(e){ e.preventDefault(); performAjaxSearch(); });

function performAjaxSearch(){
    const formData = new FormData(searchForm);
    const params = new URLSearchParams(formData);
    const url = `<?php echo e(route('books.catalog')); ?>?${params.toString()}`;
    loadingOverlay.classList.remove('hidden');
    fetch(url, { method:'GET', headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'text/html' }})
        .then(r => { if(!r.ok) throw new Error('Network error'); return r.text(); })
        .then(html => { tableContainer.innerHTML = html; loadingOverlay.classList.add('hidden'); window.history.pushState({}, '', url); attachPaginationListeners(); })
        .catch(err => { console.error(err); loadingOverlay.classList.add('hidden'); alert('Failed to load books.'); });
}

function attachPaginationListeners(){
    const links = document.querySelectorAll('#booksTableContainer .pagination a');
    links.forEach(a => a.addEventListener('click', function(e){
        e.preventDefault();
        const url = this.getAttribute('href');
        if(!url) return;
        loadingOverlay.classList.remove('hidden');
        fetch(url, { method:'GET', headers: { 'X-Requested-With':'XMLHttpRequest', 'Accept':'text/html' }})
            .then(r => { if(!r.ok) throw new Error('Network error'); return r.text(); })
            .then(html => { tableContainer.innerHTML = html; loadingOverlay.classList.add('hidden'); window.history.pushState({}, '', url); attachPaginationListeners(); })
            .catch(err => { console.error(err); loadingOverlay.classList.add('hidden'); alert('Failed to load page.'); });
    }));
}

document.addEventListener('DOMContentLoaded', attachPaginationListeners);
</script>
</body>
</html>
<?php /**PATH C:\Users\User\Documents\GitHub\tarumt-librarySystem\resources\views/books/student-book.blade.php ENDPATH**/ ?>