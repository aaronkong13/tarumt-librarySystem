<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Fines - BookHub</title>
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
                    <h2 class="text-xl font-bold text-gray-900">My Fines</h2>
                    <p class="text-sm text-gray-500">View and pay your library fines</p>
                </div>
                <a href="{{ route('books.catalog') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-book mr-2"></i> Browse Books
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

                <!-- Summary Card -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-6 mb-8 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold opacity-90">Fine Summary</h3>
                            <p class="text-3xl font-bold mt-2">RM {{ number_format($summary['total_unpaid_amount'], 2) }}</p>
                            <p class="text-sm opacity-75 mt-1">Total Unpaid Balance</p>
                        </div>
                        <div class="text-right">
                            <div class="flex gap-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold">{{ $summary['unpaid_count'] }}</p>
                                    <p class="text-xs opacity-75">Unpaid</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold">{{ $summary['paid_count'] }}</p>
                                    <p class="text-xs opacity-75">Paid</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-2xl font-bold">{{ $summary['waived_count'] }}</p>
                                    <p class="text-xs opacity-75">Waived</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($summary['total_unpaid_amount'] > 0)
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <div class="flex items-center gap-2 text-amber-200">
                            <i class="fa-solid fa-exclamation-circle"></i>
                            <span class="text-sm">Please pay your outstanding fines to continue borrowing books.</span>
                        </div>
                    </div>
                    @endif
                </div>

                @php
                    $fineCollection = collect($fines);
                    $unpaidFines = $fineCollection->where('status', 'unpaid');
                    $paidFines = $fineCollection->where('status', 'paid');
                    $waivedFines = $fineCollection->where('status', 'waived');
                @endphp

                <!-- Pay All Button -->
                @if($unpaidFines->isNotEmpty())
                <div class="mb-6 flex justify-end">
                    <button type="button"
                            onclick="openPaymentModal('all', {{ $summary['total_unpaid_amount'] }})"
                            class="px-6 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition-colors shadow-lg shadow-green-600/30">
                        <i class="fa-solid fa-wallet mr-2"></i>
                        Pay All (RM {{ number_format($summary['total_unpaid_amount'], 2) }})
                    </button>
                </div>
                @endif

                <!-- Unpaid Fines -->
                @if($unpaidFines->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-red-200 mb-8">
                    <div class="px-6 py-4 border-b border-red-100 bg-red-50 rounded-t-2xl">
                        <h3 class="text-lg font-semibold text-red-700">
                            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                            Outstanding Fines ({{ $unpaidFines->count() }})
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($unpaidFines as $fine)
                                <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                                    <i class="fa-solid fa-book text-red-600"></i>
                                                </div>
                                                <div>
                                                    @if(isset($fine['borrowing']) && isset($fine['borrowing']['book']))
                                                    <p class="font-semibold text-gray-900">{{ $fine['borrowing']['book']['title'] }}</p>
                                                    <p class="text-sm text-gray-500">{{ $fine['borrowing']['book']['author'] ?? 'Unknown Author' }}</p>
                                                    @else
                                                    <p class="font-semibold text-gray-500">Book information unavailable</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="mt-3 flex items-center gap-4 text-sm">
                                                <span class="text-gray-600">
                                                    <i class="fa-regular fa-calendar mr-1"></i>
                                                    {{ \Carbon\Carbon::parse($fine['created_at'])->format('M d, Y') }}
                                                </span>
                                                <span class="text-gray-600">{{ $fine['reason'] }}</span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-red-600">RM {{ number_format($fine['amount'], 2) }}</p>
                                            <button type="button"
                                                    onclick="openPaymentModal('{{ $fine['id'] }}', {{ $fine['amount'] }})"
                                                    class="mt-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                                <i class="fa-solid fa-credit-card mr-1"></i> Pay Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Payment History -->
                @if($paidFines->isNotEmpty() || $waivedFines->isNotEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fa-solid fa-history text-gray-600 mr-2"></i>
                            Fine History
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($paidFines->merge($waivedFines)->sortByDesc('updated_at') as $fine)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 {{ $fine['status'] === 'paid' ? 'bg-green-100' : 'bg-amber-100' }} rounded-lg flex items-center justify-center">
                                            <i class="fa-solid {{ $fine['status'] === 'paid' ? 'fa-check text-green-600' : 'fa-hand-holding-dollar text-amber-600' }}"></i>
                                        </div>
                                        <div>
                                            @if(isset($fine['borrowing']) && isset($fine['borrowing']['book']))
                                            <p class="font-medium text-gray-900">{{ $fine['borrowing']['book']['title'] }}</p>
                                            @else
                                            <p class="font-medium text-gray-500">Book information unavailable</p>
                                            @endif
                                            <p class="text-sm text-gray-500">{{ $fine['reason'] }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold {{ $fine['status'] === 'paid' ? 'text-green-600' : 'text-amber-600' }}">
                                            RM {{ number_format($fine['amount'], 2) }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $fine['status'] === 'paid' ? 'Paid' : 'Waived' }} on
                                            {{ $fine['paid_date'] ? \Carbon\Carbon::parse($fine['paid_date'])->format('M d, Y') : \Carbon\Carbon::parse($fine['updated_at'])->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if(collect($fines)->isEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-check text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Fines</h3>
                        <p class="text-gray-500">You have no fines. Keep returning books on time!</p>
                        <a href="{{ route('books.catalog') }}" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                            Browse Books
                        </a>
                    </div>
                @endif

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500">
                Secure Library Management System &copy; {{ date('Y') }}
            </footer>
        </main>

    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-500 to-emerald-600 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fa-solid fa-building-columns text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Bank Payment</h3>
                            <p class="text-sm text-white/80">Enter your bank credentials</p>
                        </div>
                    </div>
                    <button onclick="closePaymentModal()" class="text-white/80 hover:text-white transition-colors">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- Amount Display -->
                <div class="mb-6 p-4 bg-gray-50 rounded-xl text-center">
                    <p class="text-sm text-gray-500 mb-1">Payment Amount</p>
                    <p class="text-3xl font-bold text-green-600" id="paymentAmount">RM 0.00</p>
                </div>

                <!-- Bank Form -->
                <form id="paymentForm" onsubmit="processPayment(event)">
                    <input type="hidden" id="paymentFineId" value="">

                    <div class="space-y-4">
                        <!-- Bank Account Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fa-solid fa-credit-card mr-2 text-gray-400"></i>
                                Bank Account Number
                            </label>
                            <input type="text"
                                   id="bankAccount"
                                   placeholder="Enter your bank account number"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                   required>
                        </div>

                        <!-- Bank Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fa-solid fa-lock mr-2 text-gray-400"></i>
                                Bank Password
                            </label>
                            <div class="relative">
                                <input type="password"
                                       id="bankPassword"
                                       placeholder="Enter your bank password"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"
                                       required>
                                <button type="button"
                                        onclick="togglePassword()"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div id="paymentError" class="hidden p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm">
                            <i class="fa-solid fa-exclamation-circle mr-2"></i>
                            <span id="errorMessage"></span>
                        </div>

                        {{-- <!-- Info Note -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-600 text-sm">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            Demo credentials: Account: <strong>1234567890</strong>, Password: <strong>password123</strong>
                        </div> --}}
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 mt-6">
                        <button type="button"
                                onclick="closePaymentModal()"
                                class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                id="payButton"
                                class="flex-1 px-4 py-3 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-colors">
                            <i class="fa-solid fa-check mr-2"></i>
                            Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Hardcoded bank credentials for demo
        const VALID_BANK_ACCOUNT = '1234567890';
        const VALID_BANK_PASSWORD = 'password123';

        // Open payment modal
        function openPaymentModal(fineId, amount) {
            document.getElementById('paymentFineId').value = fineId;
            document.getElementById('paymentAmount').textContent = 'RM ' + amount.toFixed(2);
            document.getElementById('bankAccount').value = '';
            document.getElementById('bankPassword').value = '';
            document.getElementById('paymentError').classList.add('hidden');
            document.getElementById('paymentModal').classList.remove('hidden');
            document.getElementById('paymentModal').classList.add('flex');
        }

        // Close payment modal
        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.getElementById('paymentModal').classList.remove('flex');
        }

        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('bankPassword');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Process payment
        function processPayment(event) {
            event.preventDefault();

            const bankAccount = document.getElementById('bankAccount').value;
            const bankPassword = document.getElementById('bankPassword').value;
            const fineId = document.getElementById('paymentFineId').value;
            const errorDiv = document.getElementById('paymentError');
            const errorMessage = document.getElementById('errorMessage');
            const payButton = document.getElementById('payButton');

            // Validate credentials
            if (bankAccount !== VALID_BANK_ACCOUNT || bankPassword !== VALID_BANK_PASSWORD) {
                errorDiv.classList.remove('hidden');
                errorMessage.textContent = 'Invalid bank account or password. Please try again.';
                return;
            }

            // Hide error if validation passes
            errorDiv.classList.add('hidden');

            // Disable button and show loading
            payButton.disabled = true;
            payButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Processing...';

            // Determine the correct URL
            let paymentUrl;
            if (fineId === 'all') {
                paymentUrl = '{{ route('fines.pay-all') }}';
            } else {
                paymentUrl = '{{ url('fines') }}/' + fineId + '/pay';
            }

            // Submit payment
            fetch(paymentUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    payment_method: 'online'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closePaymentModal();
                    alert('Payment successful! Thank you.');
                    window.location.reload();
                } else {
                    errorDiv.classList.remove('hidden');
                    errorMessage.textContent = data.message || 'Payment failed. Please try again.';
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Confirm Payment';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorDiv.classList.remove('hidden');
                errorMessage.textContent = 'An error occurred. Please try again.';
                payButton.disabled = false;
                payButton.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Confirm Payment';
            });
        }

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>

</body>
</html>
