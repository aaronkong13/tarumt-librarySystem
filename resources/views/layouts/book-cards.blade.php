<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
@forelse($books as $book)
    <div class="group bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-sm font-bold text-indigo-600 bg-indigo-50 overflow-hidden">
                    @if($book->cover_image)
                        <img src="data:image/jpeg;base64,{{ base64_encode($book->cover_image) }}" class="w-full h-full object-cover book-cover-clickable" onclick="openImageModal(this.src, '{{ addslashes($book->title) }}')" alt="cover">
                    @else
                        {{ substr($book->title, 0, 1) }}
                    @endif
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full
                    @if($book->status === 'Available') bg-green-100 text-green-700 border border-green-200 @elseif($book->status === 'Borrowed') bg-amber-100 text-amber-700 border border-amber-200 @elseif($book->status === 'Reserved') bg-purple-100 text-purple-700 border border-purple-200 @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                    <i class="fa-solid fa-circle text-[6px]"></i>
                    {{ strtolower($book->status) }}
                </span>
            </div>
            <!-- No edit/delete for users -->
        </div>

        <div class="mb-2">
            <h3 class="text-base font-semibold text-gray-900 leading-snug">{{ $book->title }}</h3>
            <p class="text-xs text-gray-600 mt-1">{{ $book->author }}</p>
        </div>

        <div class="flex items-center justify-between pt-4 mt-2 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <span class="text-[11px] px-2 py-1 rounded-md bg-gray-100 text-gray-700">{{ $book->category }}</span>
            </div>
            <span class="text-[11px] text-gray-500 font-mono">{{ $book->isbn }}</span>
        </div>
    </div>
@empty
    <div class="col-span-full">
        <div class="py-12 text-center text-gray-500 bg-white border border-dashed border-gray-200 rounded-2xl">
            <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
            <p class="text-lg font-medium">No books found</p>
        </div>
    </div>
@endforelse
</div>

<div class="mt-6 pagination">
    {{ $books->links() }}
</div>
