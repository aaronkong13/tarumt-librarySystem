<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
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
        <?php echo $__env->make('layouts.sidebar', ['active' => 'fines'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="flex-1 md:ml-64 relative">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Fines Management</h2>
                    <p class="text-sm text-gray-500">View and manage library fines</p>
                </div>
                <div class="flex gap-3">
                    <?php if(in_array(auth()->user()->role, ['Staff', 'Admin'])): ?>
                    <button onclick="processOverdueFines()" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors">
                        <i class="fa-solid fa-sync mr-2"></i> Process Overdue
                    </button>
                    <?php endif; ?>
                    <a href="<?php echo e(route('borrowings.index')); ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Back
                    </a>
                </div>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <!-- Flash Messages -->
                <?php if(session('success')): ?>
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative" role="alert">
                        <span class="block sm:inline"><?php echo e(session('success')); ?></span>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative" role="alert">
                        <span class="block sm:inline"><?php echo e(session('error')); ?></span>
                    </div>
                <?php endif; ?>

                <?php
                    // Determine if the current user is staff/admin (can be overridden by controller)
                    $isStaff = $isStaff ?? in_array(auth()->user()->role, ['Staff', 'Admin']);

                    // Controller passes $fines (from FineService), convert to collection if needed
                    $allFines = collect($fines ?? []);

                    $unpaidFines = $allFines->where('status', 'unpaid');
                    $paidFines   = $allFines->where('status', 'paid');
                    $waivedFines = $allFines->where('status', 'waived');
                ?>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Unpaid Fines</p>
                                <p class="text-3xl font-bold text-red-600">RM <?php echo e(number_format($unpaidFines->sum('amount'), 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo e($unpaidFines->count()); ?> fines</p>
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
                                <p class="text-3xl font-bold text-green-600">RM <?php echo e(number_format($paidFines->sum('amount'), 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo e($paidFines->count()); ?> fines</p>
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
                                <p class="text-3xl font-bold text-amber-600">RM <?php echo e(number_format($waivedFines->sum('amount'), 2)); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo e($waivedFines->count()); ?> fines</p>
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
                                <p class="text-3xl font-bold text-gray-600"><?php echo e($allFines->count()); ?></p>
                                <p class="text-xs text-gray-400 mt-1">All time</p>
                            </div>
                            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-receipt text-gray-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pay All Button for Users with Unpaid Fines -->
                <?php if($unpaidFines->isNotEmpty()): ?>
                <div class="mb-6 flex justify-end">
                    <?php if(!$isStaff): ?>
                    <button type="button"
                            onclick="openPaymentModal('all', <?php echo e($unpaidFines->sum('amount')); ?>)"
                            class="px-6 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition-colors shadow-lg shadow-green-600/30">
                        <i class="fa-solid fa-wallet mr-2"></i>
                        Pay All Fines (RM <?php echo e(number_format($unpaidFines->sum('amount'), 2)); ?>)
                    </button>
                    <?php else: ?>
                    <form action="<?php echo e(route('fines.pay-all')); ?>" method="POST" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit"
                                class="px-6 py-3 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700 transition-colors shadow-lg shadow-green-600/30"
                                onclick="return confirm('Pay all unpaid fines totaling RM <?php echo e(number_format($unpaidFines->sum('amount'), 2)); ?>?')">
                            <i class="fa-solid fa-wallet mr-2"></i>
                            Pay All Fines (RM <?php echo e(number_format($unpaidFines->sum('amount'), 2)); ?>)
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Unpaid Fines -->
                <?php if($unpaidFines->isNotEmpty()): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-red-200 mb-8">
                    <div class="px-6 py-4 border-b border-red-100 bg-red-50 rounded-t-2xl flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-red-700">
                            <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                            Unpaid Fines (<?php echo e($unpaidFines->count()); ?>)
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        <?php if($isStaff): ?>
                                        <th class="pb-3 font-medium">User</th>
                                        <?php endif; ?>
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Date</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php $__currentLoopData = $unpaidFines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <?php if($isStaff): ?>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold">
                                                    #<?php echo e($fine->user->id); ?>

                                                </span>
                                                <p class="text-sm text-gray-900 mt-1"><?php echo e($fine->user->name); ?></p>
                                            </td>
                                            <?php endif; ?>
                                            <td class="py-4">
                                                <?php if($fine->borrowing && $fine->borrowing->book): ?>
                                                <p class="font-medium text-gray-900"><?php echo e($fine->borrowing->book->title); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo e($fine->borrowing->book->author); ?></p>
                                                <?php else: ?>
                                                <p class="text-gray-500">N/A</p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-sm text-gray-600"><?php echo e($fine->reason); ?></td>
                                            <td class="py-4 text-sm text-gray-600"><?php echo e($fine->created_at->format('M d, Y')); ?></td>
                                            <td class="py-4">
                                                <span class="font-bold text-red-600">RM <?php echo e(number_format($fine->amount, 2)); ?></span>
                                            </td>
                                            <td class="py-4">
                                                <div class="flex gap-2">
                                                    <?php if(!$isStaff): ?>
                                                    <button type="button"
                                                            onclick="openPaymentModal('<?php echo e($fine->id); ?>', <?php echo e($fine->amount); ?>)"
                                                            class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                                        <i class="fa-solid fa-check mr-1"></i> Pay
                                                    </button>
                                                    <?php else: ?>
                                                    <form action="<?php echo e(route('fines.pay', $fine->id)); ?>" method="POST" class="inline">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit"
                                                                class="px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                                                                onclick="return confirm('Confirm payment of RM <?php echo e(number_format($fine->amount, 2)); ?>?')">
                                                            <i class="fa-solid fa-check mr-1"></i> Pay
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <?php if($isStaff): ?>
                                                    <form action="<?php echo e(route('fines.waive', $fine->id)); ?>" method="POST" class="inline">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit"
                                                                class="px-3 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors"
                                                                onclick="return confirm('Waive fine of RM <?php echo e(number_format($fine->amount, 2)); ?>?')">
                                                            <i class="fa-solid fa-hand-holding-dollar mr-1"></i> Waive
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-red-50">
                                        <td colspan="<?php echo e($isStaff ? 4 : 3); ?>" class="py-3 px-4 font-semibold text-red-700">Total Unpaid</td>
                                        <td colspan="2" class="py-3 font-bold text-red-700">RM <?php echo e(number_format($unpaidFines->sum('amount'), 2)); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Paid Fines -->
                <?php if($paidFines->isNotEmpty()): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fa-solid fa-check-circle text-green-600 mr-2"></i>
                            Payment History (<?php echo e($paidFines->count()); ?>)
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        <?php if($isStaff): ?>
                                        <th class="pb-3 font-medium">User</th>
                                        <?php endif; ?>
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Paid On</th>
                                        <th class="pb-3 font-medium">Method</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php $__currentLoopData = $paidFines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <?php if($isStaff): ?>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">
                                                    #<?php echo e($fine->user->id); ?>

                                                </span>
                                                <p class="text-sm text-gray-900 mt-1"><?php echo e($fine->user->name); ?></p>
                                            </td>
                                            <?php endif; ?>
                                            <td class="py-4">
                                                <?php if($fine->borrowing && $fine->borrowing->book): ?>
                                                <p class="text-gray-900"><?php echo e($fine->borrowing->book->title); ?></p>
                                                <?php else: ?>
                                                <p class="text-gray-500">N/A</p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-sm text-gray-600"><?php echo e($fine->reason); ?></td>
                                            <td class="py-4 text-green-600 font-medium">RM <?php echo e(number_format($fine->amount, 2)); ?></td>
                                            <td class="py-4 text-sm text-gray-600">
                                                <?php echo e($fine->paid_date ? $fine->paid_date->format('M d, Y') : '-'); ?>

                                            </td>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium capitalize">
                                                    <?php echo e($fine->payment_method ?? 'Cash'); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Waived Fines -->
                <?php if($waivedFines->isNotEmpty()): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-700">
                            <i class="fa-solid fa-hand-holding-dollar text-amber-600 mr-2"></i>
                            Waived Fines (<?php echo e($waivedFines->count()); ?>)
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                        <?php if($isStaff): ?>
                                        <th class="pb-3 font-medium">User</th>
                                        <?php endif; ?>
                                        <th class="pb-3 font-medium">Book</th>
                                        <th class="pb-3 font-medium">Reason</th>
                                        <th class="pb-3 font-medium">Amount</th>
                                        <th class="pb-3 font-medium">Waived On</th>
                                        <th class="pb-3 font-medium">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php $__currentLoopData = $waivedFines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <?php if($isStaff): ?>
                                            <td class="py-4">
                                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">
                                                    #<?php echo e($fine->user->id); ?>

                                                </span>
                                                <p class="text-sm text-gray-900 mt-1"><?php echo e($fine->user->name); ?></p>
                                            </td>
                                            <?php endif; ?>
                                            <td class="py-4">
                                                <?php if($fine->borrowing && $fine->borrowing->book): ?>
                                                <p class="text-gray-900"><?php echo e($fine->borrowing->book->title); ?></p>
                                                <?php else: ?>
                                                <p class="text-gray-500">N/A</p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-sm text-gray-600"><?php echo e($fine->reason); ?></td>
                                            <td class="py-4 text-amber-600 font-medium">RM <?php echo e(number_format($fine->amount, 2)); ?></td>
                                            <td class="py-4 text-sm text-gray-600"><?php echo e($fine->updated_at->format('M d, Y')); ?></td>
                                            <td class="py-4 text-sm text-gray-500"><?php echo e($fine->notes ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($allFines->isEmpty()): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-check text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Fines</h3>
                        <p class="text-gray-500"><?php echo e($isStaff ? 'There are no fine records in the system.' : 'You have no fines. Keep up the good work!'); ?></p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500">
                Secure Library Management System &copy; <?php echo e(date('Y')); ?>

            </footer>
        </main>

    </div>

    <!-- Payment Modal for Students -->
    <?php if(!$isStaff): ?>
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

                        <!-- Info Note -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-600 text-sm">
                            <i class="fa-solid fa-info-circle mr-2"></i>
                            Demo credentials: Account: <strong>1234567890</strong>, Password: <strong>password123</strong>
                        </div>
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
    <?php endif; ?>

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
                paymentUrl = '<?php echo e(route('fines.pay-all')); ?>';
            } else {
                paymentUrl = '<?php echo e(url('fines')); ?>/' + fineId + '/pay';
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
        document.getElementById('paymentModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });

        function processOverdueFines() {
            if (!confirm('Process all overdue borrowings and generate fines?')) {
                return;
            }

            fetch('<?php echo e(route('fines.process-overdue')); ?>', {
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
<?php /**PATH C:\Users\Cheng\Documents\GitHub\tarumt-librarySystem\resources\views/fines/index.blade.php ENDPATH**/ ?>