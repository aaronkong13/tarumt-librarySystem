<!DOCTYPE html>
<html>
<head>
    <title>Book Security Check</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4 text-blue-600">Add New Book (Security Test)</h2>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data">
            @csrf <div class="mb-4">
                <label class="block font-bold mb-1">Book Title</label>
                <input type="text" name="title" class="w-full border p-2 rounded" placeholder="Enter title (Try <script>...)" required>
                <p class="text-xs text-gray-500 mt-1">Security: HTML tags will be stripped.</p>
            </div>

            <div class="mb-4">
                <label class="block font-bold mb-1">ISBN</label>
                <input type="text" name="isbn" class="w-full border p-2 rounded" placeholder="e.g. 978-1-234" required>
                <p class="text-xs text-gray-500 mt-1">Security: Only numbers and dashes allowed.</p>
            </div>

            <div class="mb-4">
                <label class="block font-bold mb-1">Year</label>
                <input type="text" name="year" class="w-full border p-2 rounded" placeholder="e.g. 2025" required>
                <p class="text-xs text-gray-500 mt-1">Security: Must be an integer.</p>
            </div>

            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                Test Security Check
            </button>
        </form>
    </div>

</body>
</html>