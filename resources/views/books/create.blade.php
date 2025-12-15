<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-900/50 min-h-screen flex items-center justify-center p-4 backdrop-blur-sm">

    <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden relative">
        
        <div class="flex justify-between items-center px-8 py-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Add New Book</h2>
            <a href="{{ route('books.index') }}" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </a>
        </div>

        <div class="p-8">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-100 flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-green-500"></i>
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                        <h3 class="text-sm font-bold text-red-800">Please fix the following errors:</h3>
                    </div>
                    <ul class="list-disc list-inside text-sm text-red-700 ml-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data" charset="UTF-8" class="space-y-6">
                @csrf
                
                <div>
                    <label class="block mb-2 text-sm font-bold text-gray-900">Book Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" 
                        class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 outline-none transition-all" 
                        placeholder="Enter book title" required>
                    <p class="mt-1 text-xs text-gray-400">Title will be sanitized before saving.</p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-bold text-gray-900">Author</label>
                    <input type="text" name="author" value="{{ old('author') }}" 
                        class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 outline-none transition-all" 
                        placeholder="Enter author name" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-900">ISBN</label>
                        <input type="text" name="isbn" value="{{ old('isbn') }}" 
                            class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 outline-none transition-all" 
                            placeholder="978-0000000000" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-900">Published Year</label>
                        <input type="number" name="year" value="{{ old('year') }}" 
                            class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 outline-none transition-all" 
                            placeholder="2025" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-900">Category</label>
                        <div class="relative">
                            <select name="category" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 appearance-none cursor-pointer outline-none transition-all" required>
                                <option value="">Select a category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-900">Status</label>
                        <div class="relative">
                            <select name="status" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 appearance-none cursor-pointer outline-none transition-all" required>
                                @foreach(['Available','Borrowed','Lost','Damaged'] as $status)
                                    <option value="{{ $status }}" {{ old('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-bold text-gray-900">Cover Image</label>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-400 mt-1">JPG, PNG or GIF (MAX. 2MB)</p>
                                </div>
                                <input type="file" name="cover" class="hidden" id="coverInput" accept="image/*" />
                            </label>
                        </div>
                        <!-- Image Preview -->
                        <div id="previewContainer" class="hidden">
                            <p class="text-xs text-gray-500 mb-2">Preview:</p>
                            <img id="previewImage" src="" alt="Preview" class="h-40 w-32 object-cover rounded-lg border border-indigo-200 shadow-md bg-gray-100">
                        </div>
                    </div>
                </div>

                <script>
                    const coverInput = document.getElementById('coverInput');
                    const previewContainer = document.getElementById('previewContainer');
                    const previewImage = document.getElementById('previewImage');

                    coverInput.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                previewImage.src = event.target.result;
                                previewContainer.classList.remove('hidden');
                            };
                            reader.readAsDataURL(file);
                        } else {
                            previewContainer.classList.add('hidden');
                        }
                    });
                </script>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50 mt-8">
                    <a href="{{ route('books.index') }}" class="px-6 py-3 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 focus:ring-4 focus:ring-gray-100 transition-all">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition-all shadow-lg shadow-indigo-200">
                        <i class="fa-solid fa-plus mr-2"></i> Add Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>