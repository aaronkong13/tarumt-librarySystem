<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History - BookHub</title>
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

                <a href="{{ route('books.index') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-book w-6"></i>
                    <span class="font-medium text-sm">Books Management</span>
                </a>

                <a href="{{ route('borrowings.index') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-hand-holding w-6"></i>
                    <span class="font-medium text-sm">Borrow & Return</span>
                </a>

                @if(in_array(Auth::user()->role, ['Staff', 'Admin']))
                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-users w-6"></i>
                    <span class="font-medium text-sm">User Management</span>
                </a>
                @endif

                <a href="{{ route('borrowings.history') }}" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
                    <i class="fa-solid fa-clock-rotate-left w-6"></i>
                    <span class="font-medium text-sm">History</span>
                </a>

                <a href="{{ route('borrowings.fines') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-money-bill w-6"></i>
                    <span class="font-medium text-sm">Fines</span>
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

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Borrowing History</h2>
                    <p class="text-sm text-gray-500">View all borrowing records</p>
                </div>
                <a href="{{ route('borrowings.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back
                </a>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                @php
                    $history = App\Models\Borrowing::with(['book', 'user', 'fines'])->orderBy('created_at', 'desc')->get();
                @endphp

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Borrowed</p>
                                <p class="text-3xl font-bold text-indigo-600">{{ $history->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-book-bookmark text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Returned</p>
                                <p class="text-3xl font-bold text-green-600">{{ $history->where('status', 'returned')->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-check text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Currently Borrowed</p>
                                <p class="text-3xl font-bold text-amber-600">{{ $history->where('status', 'borrowed')->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Fines</p>
                                <p class="text-3xl font-bold text-red-600">RM {{ number_format($history->flatMap->fines->sum('amount'), 2) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-money-bill text-red-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fa-solid fa-clock-rotate-left text-indigo-600 mr-2"></i>
                            All Borrowing Records
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($history->isEmpty())
                            <p class="text-gray-500 text-center py-8">No borrowing records found.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                            <th class="pb-3 font-medium">#</th>
                                            <th class="pb-3 font-medium">User</th>
                                            <th class="pb-3 font-medium">Book</th>
                                            <th class="pb-3 font-medium">Borrowed</th>
                                            <th class="pb-3 font-medium">Due Date</th>
                                            <th class="pb-3 font-medium">Returned</th>
                                            <th class="pb-3 font-medium">Status</th>
                                            <th class="pb-3 font-medium">Fine</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($history as $index => $borrowing)
                                            <tr>
                                                <td class="py-4 text-sm text-gray-600">{{ $index + 1 }}</td>
                                                <td class="py-4">
                                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold">
                                                        #{{ $borrowing->user->id }}
                                                    </span>
                                                    <p class="text-sm text-gray-900 mt-1">{{ $borrowing->user->name }}</p>
                                                </td>
                                                <td class="py-4">
                                                    <p class="font-medium text-gray-900">{{ Str::limit($borrowing->book->title, 25) }}</p>
                                                    <p class="text-sm text-gray-500">{{ $borrowing->book->author }}</p>
                                                </td>
                                                <td class="py-4 text-sm text-gray-600">{{ $borrowing->borrow_date->format('M d, Y') }}</td>
                                                <td class="py-4 text-sm text-gray-600">{{ $borrowing->due_date->format('M d, Y') }}</td>
                                                <td class="py-4 text-sm text-gray-600">
                                                    {{ $borrowing->return_date ? $borrowing->return_date->format('M d, Y') : '-' }}
                                                </td>
                                                <td class="py-4">
                                                    @if($borrowing->status === 'returned')
                                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium">Returned</span>
                                                    @elseif($borrowing->due_date->isPast())
                                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-medium">Overdue</span>
                                                    @else
                                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium">Borrowed</span>
                                                    @endif
                                                </td>
                                                <td class="py-4">
                                                    @php $totalFine = $borrowing->fines->sum('amount'); @endphp
                                                    @if($totalFine > 0)
                                                        <span class="text-red-600 font-medium">RM {{ number_format($totalFine, 2) }}</span>
                                                        @if($borrowing->fines->where('status', 'unpaid')->count() > 0)
                                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs ml-1">Unpaid</span>
                                                        @else
                                                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs ml-1">Paid</span>
                                                        @endif
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500">
                Secure Library Management System &copy; {{ date('Y') }}
            </footer>
        </main>

    </div>

</body>
</html>
