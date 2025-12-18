<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reservation Queues - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#F3F4F6] text-gray-800">

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        @include('layouts.sidebar', ['active' => 'reservations'])

        <main class="flex-1 md:ml-64 relative">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">All Reservation Queues</h2>
                    <p class="text-sm text-gray-500">Books with active reservations</p>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                @if(count($books) > 0)
                    <div class="grid grid-cols-1 gap-6">
                        @foreach($books as $book)
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-4 flex-1">
                                        <!-- Book Cover -->
                                        <div class="flex-shrink-0">
                                            @if($book->cover_image)
                                                <img src="data:image/jpeg;base64,{{ base64_encode($book->cover_image) }}" 
                                                     class="w-16 h-24 object-cover rounded-lg shadow" 
                                                     alt="{{ $book->title }}">
                                            @else
                                                <div class="w-16 h-24 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg shadow flex items-center justify-center">
                                                    <span class="text-white font-bold text-lg">{{ substr($book->title, 0, 1) }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Book Info -->
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $book->title }}</h3>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        <i class="fa-solid fa-user-pen mr-1"></i>{{ $book->author }}
                                                        <span class="mx-2">•</span>
                                                        <i class="fa-solid fa-barcode mr-1"></i>{{ $book->isbn }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-4">
                                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium
                                                    @if($book->status === 'Available') bg-green-100 text-green-700 border border-green-200
                                                    @elseif($book->status === 'Borrowed') bg-amber-100 text-amber-700 border border-amber-200
                                                    @elseif($book->status === 'Reserved') bg-purple-100 text-purple-700 border border-purple-200
                                                    @else bg-gray-100 text-gray-700 border border-gray-200 @endif">
                                                    <i class="fa-solid fa-circle text-[8px]"></i>
                                                    {{ $book->status }}
                                                </span>

                                                <div class="flex items-center gap-2 text-sm">
                                                    <div class="flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-200">
                                                        <i class="fa-solid fa-users text-xs"></i>
                                                        <span class="font-semibold">{{ $book->reservations_count }}</span>
                                                        <span>{{ $book->reservations_count === 1 ? 'reservation' : 'reservations' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex-shrink-0 ml-4">
                                        <a href="{{ route('reservations.queue', $book->bookId) }}" 
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                            <i class="fa-solid fa-list"></i>
                                            <span>View Queue</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-clipboard-list text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Active Reservation Queues</h3>
                        <p class="text-gray-600">There are no books with active reservations at the moment.</p>
                    </div>
                @endif

            </div>

        </main>

    </div>

</body>
</html>
