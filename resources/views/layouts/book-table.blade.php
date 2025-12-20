<table class="w-full text-left border-collapse">
    <thead>
        <tr class="bg-white border-b border-gray-100">
            <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Book Title</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Author</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">ISBN</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Category</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
            <th class="py-4 px-6 text-xs font-bold text-gray-400 uppercase tracking-wider">Circulation</th>
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
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                        {{ $book->status }}
                    </span>
                @endif
            </td>
            <td class="py-4 px-6">
                <button onclick="openBorrowingHistoryModal({{ $book->bookId }}, '{{ addslashes($book->title) }}')" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors border border-indigo-200">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>{{ $book->borrowing_stats['total_borrows'] ?? 0 }} Total</span>
                </button>
            </td>
            <td class="py-4 px-6 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="openAssignModal({{ $book->bookId }}, '{{ addslashes($book->title) }}', '{{ $book->author }}', '{{ $book->isbn }}', '{{ $book->category }}', '{{ $book->status }}')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Assign Book">
                        <i class="fa-solid fa-user-plus text-xs"></i>
                    </button>
                    <a href="{{ route('books.edit', $book) }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </a>
                    <form action="{{ route('books.destroy', $book->bookId) }}" method="POST" onsubmit="return confirm('Delete this book?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Delete Book">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="py-12 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p class="text-lg font-medium">No books found</p>
                </div>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="mt-6 pagination">
    {{ $books->links() }}
</div>

<!-- Borrowing History Modal -->
<div id="borrowingHistoryModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-100 p-6 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Borrowing History</h3>
                <p class="text-sm text-gray-600 mt-1" id="historyBookTitle"></p>
            </div>
            <button onclick="closeBorrowingHistoryModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="p-6">
            <div id="borrowingHistoryContainer">
                <div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-gray-400 text-2xl"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Book Modal -->
<div id="assignModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Assign Book</h3>
                <button onclick="closeAssignModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-bold text-gray-500 uppercase mb-3">Book Details</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Title:</span>
                        <span class="text-sm font-medium text-gray-900" id="modalBookTitle"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Author:</span>
                        <span class="text-sm font-medium text-gray-900" id="modalBookAuthor"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">ISBN:</span>
                        <span class="text-sm font-medium font-mono text-gray-900" id="modalBookISBN"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Category:</span>
                        <span class="text-sm font-medium text-gray-900" id="modalBookCategory"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Status:</span>
                        <span class="text-sm font-bold" id="modalBookStatus"></span>
                    </div>
                </div>
            </div>

            <form id="assignForm" onsubmit="submitAssignment(event)">
                <input type="hidden" id="assignBookId" name="book_id">

                <div class="mb-4">
                    <label for="userId" class="block text-sm font-medium text-gray-700 mb-2">
                        User ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="userId" name="user_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Enter user ID">
                </div>

                <div class="mb-4" id="actionTypeContainer">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Action <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2" id="actionOptions"></div>
                </div>

                <div class="mb-6">
                    <label for="days" class="block text-sm font-medium text-gray-700 mb-2">
                        Number of Days <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="days" name="days" required min="1" max="7" value="7"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Maximum 7 days">
                    <p class="text-xs text-gray-500 mt-1">Maximum 7 days allowed</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeAssignModal()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        Assign Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentBookStatus = '';

function openAssignModal(bookId, title, author, isbn, category, status) {
    currentBookStatus = status;
    document.getElementById('modalBookTitle').textContent = title;
    document.getElementById('modalBookAuthor').textContent = author;
    document.getElementById('modalBookISBN').textContent = isbn;
    document.getElementById('modalBookCategory').textContent = category;
    const statusElement = document.getElementById('modalBookStatus');
    statusElement.textContent = status;
    statusElement.className = 'text-sm font-bold';
    if (status === 'Available') statusElement.classList.add('text-green-700');
    else if (status === 'Borrowed') statusElement.classList.add('text-amber-700');
    else statusElement.classList.add('text-red-700');
    document.getElementById('assignBookId').value = bookId;
    document.getElementById('assignForm').reset();
    document.getElementById('assignBookId').value = bookId;
    const actionOptions = document.getElementById('actionOptions');
    actionOptions.innerHTML = '';
    if (status === 'Available') {
        actionOptions.innerHTML = `
            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="action" value="book" required class="w-4 h-4 text-indigo-600">
                <span class="ml-3 text-sm font-medium text-gray-900">Book</span>
            </label>
            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="action" value="reserve" class="w-4 h-4 text-indigo-600">
                <span class="ml-3 text-sm font-medium text-gray-900">Reserve</span>
            </label>`;
    } else if (status === 'Borrowed') {
        actionOptions.innerHTML = `
            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
                <input type="radio" name="action" value="reserve" required checked class="w-4 h-4 text-indigo-600">
                <span class="ml-3 text-sm font-medium text-gray-900">Reserve Only</span>
            </label>
            <p class="text-xs text-amber-600 mt-2">This book is currently borrowed. You can only reserve it.</p>`;
    } else if (status === 'Reserved') {
        actionOptions.innerHTML = `
            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="action" value="available" required class="w-4 h-4 text-indigo-600">
                <span class="ml-3 text-sm font-medium text-gray-900">Mark as Available</span>
            </label>
            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="action" value="book" class="w-4 h-4 text-indigo-600">
                <span class="ml-3 text-sm font-medium text-gray-900">Book (Convert Reservation)</span>
            </label>
            <p class="text-xs text-blue-600 mt-2">This book is reserved. You can mark it available or convert to a booking.</p>`;
    }
    document.getElementById('assignModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function submitAssignment(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = { book_id: formData.get('book_id'), user_id: formData.get('user_id'), action: formData.get('action'), days: formData.get('days') };
    if (data.days < 1 || data.days > 7) { alert('Number of days must be between 1 and 7'); return; }
    alert(`Book will be ${data.action === 'book' ? 'booked' : data.action === 'reserve' ? 'reserved' : 'marked as available'} for user ${data.user_id} for ${data.days} day(s)`);
    closeAssignModal();
}

// Borrowing History Modal
function openBorrowingHistoryModal(bookId, bookTitle) {
    const modal = document.getElementById('borrowingHistoryModal');
    if (!modal) return; // Modal not found
    
    document.getElementById('historyBookTitle').textContent = bookTitle;
    document.getElementById('borrowingHistoryContainer').innerHTML = '<div class="text-center py-8"><i class="fa-solid fa-spinner fa-spin text-gray-400 text-2xl"></i></div>';
    
    // Fetch borrowing history from controller
    fetch(`/api/books/${bookId}/borrowing-history`, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load borrowing history');
            }

            const stats = data.borrowing_stats;
            const history = data.borrowing_stats.history || [];
            
            let html = `
                <div class="mb-6 p-4 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg border border-indigo-100">
                    <div class="grid grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-indigo-700">${stats.total_borrows}</div>
                            <div class="text-xs text-indigo-600 mt-1">Total Borrows</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-700">${stats.completed_borrows}</div>
                            <div class="text-xs text-green-600 mt-1">Completed</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-amber-700">${stats.currently_borrowed}</div>
                            <div class="text-xs text-amber-600 mt-1">Currently Out</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-700">${stats.unique_borrowers}</div>
                            <div class="text-xs text-purple-600 mt-1">Unique Users</div>
                        </div>
                    </div>
                </div>
            `;
            
            if (history.length === 0) {
                html += '<div class="text-center py-8 text-gray-500"><i class="fa-solid fa-inbox text-4xl mb-3 text-gray-300"></i><p>No borrowing history</p></div>';
            } else {
                html += '<div class="space-y-3">';
                history.forEach(record => {
                    const borrowDate = new Date(record.borrow_date).toLocaleDateString();
                    const returnDate = record.return_date ? new Date(record.return_date).toLocaleDateString() : 'Not returned';
                    const status = record.return_date ? 'Returned' : 'Borrowed';
                    const statusColor = record.return_date ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';
                    
                    html += `
                        <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <p class="font-medium text-gray-900">${record.user.name || 'Unknown User'}</p>
                                    <p class="text-xs text-gray-500">${record.user.email || ''}</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold ${statusColor}">${status}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Borrow:</span>
                                    <span class="font-medium text-gray-900 ml-2">${borrowDate}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Return:</span>
                                    <span class="font-medium text-gray-900 ml-2">${returnDate}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            }
            
            document.getElementById('borrowingHistoryContainer').innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching borrowing history:', error);
            document.getElementById('borrowingHistoryContainer').innerHTML = '<div class="text-center py-8 text-red-500"><i class="fa-solid fa-exclamation-circle text-4xl mb-3"></i><p>Failed to load borrowing history</p><p class="text-xs text-gray-500 mt-2">' + error.message + '</p></div>';
        });
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeBorrowingHistoryModal() {
    const modal = document.getElementById('borrowingHistoryModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('assignModal');
    if (event.target === modal) closeAssignModal();
});
</script>
