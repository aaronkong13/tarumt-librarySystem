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
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                        {{ $book->status }}
                    </span>
                @endif
            </td>
            <td class="py-4 px-6 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="{{ route('books.edit', $book) }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </a>
                    <button onclick="deleteBook({{ $book->bookId }}, '{{ addslashes($book->title) }}')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
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
