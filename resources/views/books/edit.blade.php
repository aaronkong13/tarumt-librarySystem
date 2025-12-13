<!DOCTYPE html>
<html>
<head>
    <title>Edit Book</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4 text-blue-600">Edit Book</h2>

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('books.update', $book) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block font-bold mb-1">Book Title</label>
                <input type="text" name="title" value="{{ old('title', $book->title) }}" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block font-bold mb-1">Author</label>
                <input type="text" name="author" value="{{ old('author', $book->author) }}" class="w-full border p-2 rounded" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold mb-1">ISBN</label>
                    <input type="text" name="isbn" value="{{ old('isbn', $book->isbn) }}" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block font-bold mb-1">Year</label>
                    <input type="number" name="year" value="{{ old('year', $book->year) }}" class="w-full border p-2 rounded" required>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold mb-1">Category</label>
                    <input type="text" name="category" value="{{ old('category', $book->category) }}" class="w-full border p-2 rounded" required>
                </div>
                <div>
                    <label class="block font-bold mb-1">Status</label>
                    <select name="status" class="w-full border p-2 rounded" required>
                        @foreach(['Available','Borrowed','Lost','Damaged'] as $status)
                            <option value="{{ $status }}" {{ old('status', $book->status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1">Cover Image</label>
                @if($book->cover_path)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $book->cover_path) }}" alt="Cover" class="h-24 rounded border">
                    </div>
                @endif
                <input type="file" name="cover" class="w-full border p-2 rounded bg-white">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Update</button>
                <a href="{{ route('books.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
