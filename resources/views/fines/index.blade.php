<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fines Management - BookHub</title>
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
        @include('layouts.sidebar', ['active' => 'fines'])

        <main class="flex-1 md:ml-64 relative">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Fines Management</h2>
                    <p class="text-sm text-gray-500">View and manage library fines</p>
                </div>
                <div class="flex gap-3">
                    @if(in_array(auth()->user()->role, ['Staff', 'Admin']))
                    <button onclick="processOverdueFines()" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors">
                        <i class="fa-solid fa-sync mr-2"></i> Process Overdue
                    </button>
                    @endif
                    <a href="{{ route('borrowings.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Back
                    </a>
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

                @php
                    // Determine if the current user is staff/admin (can be overridden by controller)
                    $isStaff = $isStaff ?? in_array(auth()->user()->role, ['Staff', 'Admin']);

                    // Controller passes $fines (from FineService), convert to collection if needed
                    $allFines = collect($fines ?? []);

                    $unpaidFines = $allFines->where('status', 'unpaid');
                    $paidFines   = $allFines->where('status', 'paid');
                    $waivedFines = $allFines->where('status', 'waived');
                @endphp

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Unpaid Fines</p>
                                <p class="text-3xl font-bold text-red-600">RM {{ number_format($unpaidFines->sum('amount'), 2) }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $unpaidFines->count() }} fines</p>
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
                                <p class="text-xs text-gray-400 mt-1">{{ $paidFines->count() }} fines</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-check text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Waived Fines</p>
                                <p class="text-3xl font-bold text-amber-600">RM {{ number_format($waivedFines->sum('amount'), 2) }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $waivedFines->count() }} fines</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Total Fine Records</p>
                                <p class="text-3xl font-bold text-gray-600">{{ $allFines->count() }}</p>
                                <p class="text-xs text-gray-400 mt-1">All time</p>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-receipt text-gray-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pay All Button for Users with Unpaid Fines -->
                @if($unpaidFines->isNotEmpty())
                <div class="mb-6 flex justify-end">
                    <form action="{{ route('fines.pay-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition-colors shadow-lg shadow-green-600/30"
                                onclick="return confirm('Pay all unpaid fines totaling RM {{ number_format($unpaidFines->sum('amount'), 2) }}?')">
                            <i class="fa-solid fa-wallet mr-2"></i>
                            Pay All Fines (RM {{ number_format($unpaidFines->sum('amount'), 2) }})
                        </button>
                    </form>
                </div>
                @endif

                <!-- Unpaid Fines -->
                @if($unpaidFines->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-red-200 mb-8">
                    <div class="px-6 py-4 border-b border-red-100 bg-red-50 rounded-t-2xl flex items-center justify-between">
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
                                        @if($isStaff)
                                        <th class="pb-3 font-medium">User</th>
                                        @endif
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Date</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($unpaidFines as $fine)
                                        <tr>
                                            @if($isStaff)
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold">
                                                    #{{ $fine->user->id }}
                                                </span>
                                                <p class="text-sm text-gray-900 mt-1">{{ $fine->user->name }}</p>
                                            </td>
                                            @endif
                                            <td class="py-4">
                                                @if($fine->borrowing && $fine->borrowing->book)
                                                <p class="font-medium text-gray-900">{{ $fine->borrowing->book->title }}</p>
                                                <p class="text-sm text-gray-500">{{ $fine->borrowing->book->author }}</p>
                                                @else
                                                <p class="text-gray-500">N/A</p>
                                                @endif
                                            </td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->reason }}</td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->created_at->format('M d, Y') }}</td>
                                            <td class="py-4">
                                                <span class="font-bold text-red-600">RM {{ number_format($fine->amount, 2) }}</span>
                                            </td>
                                            <td class="py-4">
                                                <div class="flex gap-2">
                                                    <form action="{{ route('fines.pay', $fine->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                                                                onclick="return confirm('Confirm payment of RM {{ number_format($fine->amount, 2) }}?')">
                                                            <i class="fa-solid fa-check mr-1"></i> Pay
                                                        </button>
                                                    </form>
                                                    @if($isStaff)
                                                    <form action="{{ route('fines.waive', $fine->id) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="px-3 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors"
                                                                onclick="return confirm('Waive fine of RM {{ number_format($fine->amount, 2) }}?')">
                                                            <i class="fa-solid fa-hand-holding-dollar mr-1"></i> Waive
                                                        </button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-red-50">
                                        <td colspan="{{ $isStaff ? 4 : 3 }}" class="py-3 px-4 font-semibold text-red-700">Total Unpaid</td>
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
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
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
                                        @if($isStaff)
                                        <th class="pb-3 font-medium">User</th>
                                        @endif
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
                                            @if($isStaff)
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">
                                                    #{{ $fine->user->id }}
                                                </span>
                                                <p class="text-sm text-gray-900 mt-1">{{ $fine->user->name }}</p>
                                            </td>
                                            @endif
                                            <td class="py-4">
                                                @if($fine->borrowing && $fine->borrowing->book)
                                                <p class="text-gray-900">{{ $fine->borrowing->book->title }}</p>
                                                @else
                                                <p class="text-gray-500">N/A</p>
                                                @endif
                                            </td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->reason }}</td>
                                            <td class="py-4 text-green-600 font-medium">RM {{ number_format($fine->amount, 2) }}</td>
                                            <td class="py-4 text-sm text-gray-600">
                                                {{ $fine->paid_date ? $fine->paid_date->format('M d, Y') : '-' }}
                                            </td>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium capitalize">
                                                    {{ $fine->payment_method ?? 'Cash' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Waived Fines -->
                @if($waivedFines->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fa-solid fa-hand-holding-dollar text-amber-600 mr-2"></i>
                            Waived Fines ({{ $waivedFines->count() }})
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        @if($isStaff)
                                        <th class="pb-3 font-medium">User</th>
                                        @endif
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Waived On</th>
                                        <th class="pb-3 font-medium">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($waivedFines as $fine)
                                        <tr>
                                            @if($isStaff)
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">
                                                    #{{ $fine->user->id }}
                                                </span>
                                                <p class="text-sm text-gray-900 mt-1">{{ $fine->user->name }}</p>
                                            </td>
                                            @endif
                                            <td class="py-4">
                                                @if($fine->borrowing && $fine->borrowing->book)
                                                <p class="text-gray-900">{{ $fine->borrowing->book->title }}</p>
                                                @else
                                                <p class="text-gray-500">N/A</p>
                                                @endif
                                            </td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->reason }}</td>
                                            <td class="py-4 text-amber-600 font-medium">RM {{ number_format($fine->amount, 2) }}</td>
                                            <td class="py-4 text-sm text-gray-600">{{ $fine->updated_at->format('M d, Y') }}</td>
                                            <td class="py-4 text-sm text-gray-500">{{ $fine->notes ?? '-' }}</td>
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
                        <p class="text-gray-500">{{ $isStaff ? 'There are no fine records in the system.' : 'You have no fines. Keep up the good work!' }}</p>
                    </div>
                @endif

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500">
                Secure Library Management System &copy; {{ date('Y') }}
            </footer>
        </main>

    </div>

    <script>
        function processOverdueFines() {
            if (!confirm('Process all overdue borrowings and generate fines?')) {
                return;
            }

            fetch('{{ route('fines.process-overdue') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Overdue fines processed successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing fines.');
            });
        }
    </script>

</body>
</html>
