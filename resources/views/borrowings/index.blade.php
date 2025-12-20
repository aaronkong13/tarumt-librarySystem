<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow & Return - BookHub</title>
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
        @include('layouts.sidebar', ['active' => 'borrowings'])

        <main class="flex-1 md:ml-64 relative">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Borrow & Return</h2>
                    <p class="text-sm text-gray-500">Manage book borrowings and returns</p>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Stats Cards -->
                @php
                    $allBorrowings = App\Models\Borrowing::where('status', 'borrowed')->with(['book', 'user'])->orderBy('due_date')->get();
                    $overdueCount = $allBorrowings->filter(fn($b) => $b->due_date->isPast())->count();

                    // Get ready for pickup via API query (same logic as API endpoint)
                    $reservedAvailableBooks = App\Models\Reservation::whereIn('status', ['waiting', 'notified', 'active'])
                        ->with(['book', 'user'])
                        ->whereHas('book', function($q) {
                            $q->whereIn('status', ['Available', 'Reserved'])
                              ->whereDoesntHave('borrowings', fn($bq) => $bq->where('status', 'borrowed'));
                        })
                        ->orderBy('book_id')
                        ->orderBy('queue_position')
                        ->get()
                        ->groupBy('book_id')
                        ->map(fn($group) => $group->first())
                        ->values();

                    // Get book IDs that have reservations ready for pickup
                    $reservedBookIds = $reservedAvailableBooks->pluck('book_id')->toArray();

                    // Available books WITHOUT any active reservations
                    $availableBooks = App\Models\Book::where('status', 'Available')
                        ->whereDoesntHave('borrowings', fn($q) => $q->where('status', 'borrowed'))
                        ->whereNotIn('bookId', $reservedBookIds)
                        ->get();

                    $activeReservations = App\Models\Reservation::whereIn('status', ['waiting', 'notified', 'active'])->count();
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Currently Borrowed</p>
                                <p class="text-3xl font-bold text-indigo-600">{{ $allBorrowings->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-book-bookmark text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Overdue</p>
                                <p class="text-3xl font-bold text-red-600">{{ $overdueCount }}</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-clock text-red-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Available Books</p>
                                <p class="text-3xl font-bold text-green-600">{{ $availableBooks->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-book text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Reservations</p>
                                <p class="text-3xl font-bold text-amber-600">{{ $activeReservations }}</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-bookmark text-amber-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Borrowed Books -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fa-solid fa-book-bookmark text-indigo-600 mr-2"></i>
                            Current Borrowed Books
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($allBorrowings->isEmpty())
                            <p class="text-gray-500 text-center py-8">No books are currently borrowed.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                            <th class="pb-3 font-medium">User ID</th>
                                            <th class="pb-3 font-medium">Borrower</th>
                                            <th class="pb-3 font-medium">Book</th>
                                            <th class="pb-3 font-medium">Borrowed</th>
                                            <th class="pb-3 font-medium">Due Date</th>
                                            <th class="pb-3 font-medium">Status</th>
                                            <th class="pb-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($allBorrowings as $borrowing)
                                            <tr class="{{ $borrowing->due_date->isPast() ? 'bg-red-50' : '' }}">
                                                <td class="py-4">
                                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-semibold">
                                                        #{{ $borrowing->user->id }}
                                                    </span>
                                                </td>
                                                <td class="py-4">
                                                    <p class="font-medium text-gray-900">{{ $borrowing->user->name }}</p>
                                                    <p class="text-sm text-gray-500">{{ $borrowing->user->email }}</p>
                                                </td>
                                                <td class="py-4">
                                                    <p class="font-medium text-gray-900">{{ Str::limit($borrowing->book->title, 30) }}</p>
                                                    <p class="text-sm text-gray-500">{{ $borrowing->book->author }}</p>
                                                </td>
                                                <td class="py-4 text-sm text-gray-600">{{ $borrowing->borrow_date->format('M d, Y') }}</td>
                                                <td class="py-4 text-sm text-gray-600">{{ $borrowing->due_date->format('M d, Y') }}</td>
                                                <td class="py-4">
                                                    @if($borrowing->due_date->isPast())
                                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-medium">
                                                            OVERDUE {{ $borrowing->due_date->diffForHumans() }}
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium">
                                                            {{ $borrowing->due_date->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-4">
                                                    <div class="flex gap-2">
                                                        <form action="{{ route('borrowings.return', $borrowing->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700 transition-colors" onclick="return confirm('Return this book?')">
                                                                <i class="fa-solid fa-check mr-1"></i> Return
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('borrowings.renew', $borrowing->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition-colors" onclick="return confirm('Renew for 7 more days?')">
                                                                <i class="fa-solid fa-rotate mr-1"></i> Renew
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Available Books (Not Reserved) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fa-solid fa-book text-green-600 mr-2"></i>
                            Available Books
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($availableBooks->isEmpty())
                            <p class="text-gray-500 text-center py-8">No books available at the moment.</p>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                @foreach($availableBooks as $book)
                                    <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start gap-3 mb-3">
                                            @if($book->cover_image)
                                                <img src="data:image/jpeg;base64,{{ base64_encode($book->cover_image) }}"
                                                     class="w-16 h-20 object-cover rounded-lg">
                                            @else
                                                <div class="w-16 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <i class="fa-solid fa-book text-gray-400 text-xl"></i>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-gray-900 text-sm truncate">{{ $book->title }}</h4>
                                                <p class="text-xs text-gray-500 truncate">{{ $book->author }}</p>
                                            </div>
                                        </div>
                                        <form action="{{ route('borrowings.borrow') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="book_id" value="{{ $book->bookId }}">
                                            @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
                                                <div class="mb-2">
                                                    <input type="number" name="user_id" placeholder="User ID" required
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                            @endif
                                            <button type="submit" class="w-full px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                                                <i class="fa-solid fa-plus mr-1"></i> Borrow
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Reserved Books Ready for Pickup (Book Available + Notified Reservation) -->
                @if($reservedAvailableBooks->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fa-solid fa-bell text-green-600 mr-2"></i>
                            Reserved Books - Ready for Pickup
                        </h3>
                        <p class="text-sm text-gray-500">These books are available and waiting for the reserved user to pick up</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach($reservedAvailableBooks as $reservation)
                                <div class="border-2 border-green-300 bg-green-50 rounded-xl p-4">
                                    <div class="flex items-start gap-3 mb-3">
                                        @if($reservation->book->cover_image)
                                            <img src="data:image/jpeg;base64,{{ base64_encode($reservation->book->cover_image) }}"
                                                 class="w-16 h-20 object-cover rounded-lg">
                                        @else
                                            <div class="w-16 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <i class="fa-solid fa-book text-gray-400 text-xl"></i>
                                            </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 text-sm truncate">{{ $reservation->book->title }}</h4>
                                            <p class="text-xs text-gray-500 truncate">{{ $reservation->book->author }}</p>
                                            <p class="text-xs text-green-600 mt-1 font-medium">
                                                <i class="fa-solid fa-check-circle mr-1"></i> Available Now
                                            </p>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg px-3 py-2 mb-3 border border-green-200">
                                        <span class="text-xs text-gray-500">Reserved by</span>
                                        <p class="font-semibold text-gray-900 text-sm">#{{ $reservation->user->id }} - {{ $reservation->user->name }}</p>
                                        @if($reservation->expiry_date)
                                            <p class="text-xs text-amber-600 mt-1">
                                                <i class="fa-solid fa-clock mr-1"></i>
                                                Expires: {{ $reservation->expiry_date->format('M d, Y') }}
                                            </p>
                                        @endif
                                    </div>
                                    <form action="{{ route('borrowings.borrow') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="book_id" value="{{ $reservation->book->bookId }}">
                                        <input type="hidden" name="user_id" value="{{ $reservation->user->id }}">
                                        <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors" onclick="return confirm('Issue this book to {{ $reservation->user->name }}?')">
                                            <i class="fa-solid fa-book-open mr-1"></i> Borrow for #{{ $reservation->user->id }}
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500">
                Secure Library Management System &copy; {{ date('Y') }}
            </footer>
        </main>

    </div>

</body>
</html>
