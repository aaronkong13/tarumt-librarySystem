<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fines - BookHub</title>
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

                <a href="{{ route('borrowings.history') }}" class="flex items-center px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
                    <i class="fa-solid fa-clock-rotate-left w-6"></i>
                    <span class="font-medium text-sm">History</span>
                </a>

                <a href="{{ route('borrowings.fines') }}" class="flex items-center px-4 py-3 bg-indigo-600 text-white shadow-lg shadow-indigo-900/50 rounded-xl transition-colors">
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
                    <h2 class="text-xl font-bold text-gray-900">Fines Management</h2>
                    <p class="text-sm text-gray-500">View and manage all fines</p>
                </div>
                <a href="{{ route('borrowings.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Back
                </a>
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

                @php
                    $allFines = App\Models\Fine::with(['user', 'borrowing.book'])->orderBy('created_at', 'desc')->get();
                    $unpaidFines = $allFines->where('status', 'unpaid');
                    $paidFines = $allFines->where('status', 'paid');
                @endphp

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Unpaid Fines</p>
                                <p class="text-3xl font-bold text-red-600">RM {{ number_format($unpaidFines->sum('amount'), 2) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-exclamation-triangle text-red-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Paid Fines</p>
                                <p class="text-3xl font-bold text-green-600">RM {{ number_format($paidFines->sum('amount'), 2) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-check text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Fine Records</p>
                                <p class="text-3xl font-bold text-gray-600">{{ $allFines->count() }}</p>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-receipt text-gray-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unpaid Fines -->
                @if($unpaidFines->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-red-200 mb-8">
                    <div class="px-6 py-4 border-b border-red-100 bg-red-50 rounded-t-2xl">
                        <h3 class="text-lg font-semibold text-red-700">
                            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                            Unpaid Fines ({{ $unpaidFines->count() }})
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        <th class="pb-3 font-medium">User</th>
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Date</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($unpaidFines as $fine)
                                        <tr>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold">
                                                    #{{ $fine->user->id }}
                                                </span>
                                                <p class="text-sm text-gray-900 mt-1">{{ $fine->user->name }}</p>
                                            </td>
                                            <td class="py-4">
                                                <p class="font-medium text-gray-900">{{ $fine->borrowing->book->title }}</p>
                                                <p class="text-sm text-gray-500">{{ $fine->borrowing->book->author }}</p>
                                            </td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->reason }}</td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->created_at->format('M d, Y') }}</td>
                                            <td class="py-4">
                                                <span class="font-bold text-red-600">RM {{ number_format($fine->amount, 2) }}</span>
                                            </td>
                                            <td class="py-4">
                                                <form action="{{ route('fines.pay', $fine->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors" onclick="return confirm('Confirm payment of RM {{ number_format($fine->amount, 2) }}?')">
                                                        <i class="fa-solid fa-check mr-1"></i> Mark Paid
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-red-50">
                                        <td colspan="4" class="py-3 px-4 font-semibold text-red-700">Total Unpaid</td>
                                        <td colspan="2" class="py-3 font-bold text-red-700">RM {{ number_format($unpaidFines->sum('amount'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Paid Fines -->
                @if($paidFines->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fa-solid fa-check-circle text-green-600 mr-2"></i>
                            Payment History ({{ $paidFines->count() }})
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        <th class="pb-3 font-medium">User</th>
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Paid On</th>
                                        <th class="pb-3 font-medium">Method</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($paidFines as $fine)
                                        <tr>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">
                                                    #{{ $fine->user->id }}
                                                </span>
                                                <p class="text-sm text-gray-900 mt-1">{{ $fine->user->name }}</p>
                                            </td>
                                            <td class="py-4">
                                                <p class="text-gray-900">{{ $fine->borrowing->book->title }}</p>
                                            </td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->reason }}</td>
                                            <td class="py-4 text-gray-600">RM {{ number_format($fine->amount, 2) }}</td>
                                            <td class="py-4 text-sm text-gray-600">
                                                {{ $fine->paid_date ? $fine->paid_date->format('M d, Y') : '-' }}
                                            </td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->payment_method ?? 'Cash' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                @if($allFines->isEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-check text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Fines</h3>
                        <p class="text-gray-500">There are no fine records in the system.</p>
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
