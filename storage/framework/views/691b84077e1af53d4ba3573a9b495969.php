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
        <?php echo $__env->make('layouts.sidebar', ['active' => 'borrowings'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

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

                <?php if($errors->any()): ?>
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">
                        <ul class="list-disc list-inside">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <?php
                    // Borrowing stats (same module - direct access OK)
                    $allBorrowings = App\Models\Borrowing::where('status', 'borrowed')->with(['user'])->orderBy('due_date')->get();
                    $overdueCount = $allBorrowings->filter(fn($b) => $b->due_date->isPast())->count();

                    // Reservation stats (same module - direct access OK)
                    $reservedAvailableBooks = App\Models\Reservation::whereIn('status', ['waiting', 'notified', 'active'])
                        ->with(['user'])
                        ->orderBy('book_id')
                        ->orderBy('queue_position')
                        ->get()
                        ->groupBy('book_id')
                        ->map(fn($group) => $group->first())
                        ->values();

                    $activeReservations = App\Models\Reservation::whereIn('status', ['waiting', 'notified', 'active'])->count();
                    
                    // Available books from controller (fetched via BookApiController)
                    // $availableBooks is passed from controller
                ?>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Currently Borrowed</p>
                                <p class="text-3xl font-bold text-indigo-600"><?php echo e($allBorrowings->count()); ?></p>
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
                                <p class="text-3xl font-bold text-red-600"><?php echo e($overdueCount); ?></p>
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
                                <p class="text-3xl font-bold text-green-600"><?php echo e(count($availableBooks)); ?></p>
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
                                <p class="text-3xl font-bold text-amber-600"><?php echo e($activeReservations); ?></p>
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
                        <?php if($allBorrowings->isEmpty()): ?>
                            <p class="text-gray-500 text-center py-8">No books are currently borrowed.</p>
                        <?php else: ?>
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
                                        <?php $__currentLoopData = $allBorrowings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $borrowing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr class="<?php echo e($borrowing->due_date->isPast() ? 'bg-red-50' : ''); ?>">
                                                <td class="py-4">
                                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-semibold">
                                                        #<?php echo e($borrowing->user->id); ?>

                                                    </span>
                                                </td>
                                                <td class="py-4">
                                                    <p class="font-medium text-gray-900"><?php echo e($borrowing->user->name); ?></p>
                                                    <p class="text-sm text-gray-500"><?php echo e($borrowing->user->email); ?></p>
                                                </td>
                                                <td class="py-4">
                                                    <p class="font-medium text-gray-900"><?php echo e(Str::limit($borrowing->book->title, 30)); ?></p>
                                                    <p class="text-sm text-gray-500"><?php echo e($borrowing->book->author); ?></p>
                                                </td>
                                                <td class="py-4 text-sm text-gray-600"><?php echo e($borrowing->borrow_date->format('M d, Y')); ?></td>
                                                <td class="py-4 text-sm text-gray-600"><?php echo e($borrowing->due_date->format('M d, Y')); ?></td>
                                                <td class="py-4">
                                                    <?php if($borrowing->due_date->isPast()): ?>
                                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-medium">
                                                            OVERDUE <?php echo e($borrowing->due_date->diffForHumans()); ?>

                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium">
                                                            <?php echo e($borrowing->due_date->diffForHumans()); ?>

                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-4">
                                                    <div class="flex gap-2">
                                                        <form action="<?php echo e(route('borrowings.return', $borrowing->id)); ?>" method="POST">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700 transition-colors" onclick="return confirm('Return this book?')">
                                                                <i class="fa-solid fa-check mr-1"></i> Return
                                                            </button>
                                                        </form>
                                                        <form action="<?php echo e(route('borrowings.renew', $borrowing->id)); ?>" method="POST">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition-colors" onclick="return confirm('Renew for 7 more days?')">
                                                                <i class="fa-solid fa-rotate mr-1"></i> Renew
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
                        <?php if(empty($availableBooks)): ?>
                            <p class="text-gray-500 text-center py-8">No books available at the moment.</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <?php $__currentLoopData = $availableBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        // Convert array to object for easier access
                                        $bookObj = is_array($book) ? (object)$book : $book;
                                    ?>
                                    <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start gap-3 mb-3">
                                            <?php if(isset($bookObj->cover_image) && $bookObj->cover_image): ?>
                                                <img src="<?php echo e($bookObj->cover_image); ?>"
                                                     class="w-16 h-20 object-cover rounded-lg">
                                            <?php else: ?>
                                                <div class="w-16 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <i class="fa-solid fa-book text-gray-400 text-xl"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-gray-900 text-sm truncate"><?php echo e($bookObj->title); ?></h4>
                                                <p class="text-xs text-gray-500 truncate"><?php echo e($bookObj->author); ?></p>
                                            </div>
                                        </div>
                                        <form action="<?php echo e(route('borrowings.borrow')); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="book_id" value="<?php echo e($bookObj->bookId); ?>">
                                            <?php if(in_array(Auth::user()->role, ['Staff', 'Admin'])): ?>
                                                <div class="mb-2">
                                                    <input type="number" name="user_id" placeholder="User ID" required
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                </div>
                                            <?php endif; ?>
                                            <button type="submit" class="w-full px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                                                <i class="fa-solid fa-plus mr-1"></i> Borrow
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Reserved Books Ready for Pickup (Book Available + Notified Reservation) -->
                <?php if($reservedAvailableBooks->isNotEmpty()): ?>
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
                            <?php $__currentLoopData = $reservedAvailableBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="border-2 border-green-300 bg-green-50 rounded-xl p-4">
                                    <div class="flex items-start gap-3 mb-3">
                                        <?php if($reservation->book->cover_image): ?>
                                            <img src="data:image/jpeg;base64,<?php echo e(base64_encode($reservation->book->cover_image)); ?>"
                                                 class="w-16 h-20 object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-16 h-20 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <i class="fa-solid fa-book text-gray-400 text-xl"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900 text-sm truncate"><?php echo e($reservation->book->title); ?></h4>
                                            <p class="text-xs text-gray-500 truncate"><?php echo e($reservation->book->author); ?></p>
                                            <p class="text-xs text-green-600 mt-1 font-medium">
                                                <i class="fa-solid fa-check-circle mr-1"></i> Available Now
                                            </p>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg px-3 py-2 mb-3 border border-green-200">
                                        <span class="text-xs text-gray-500">Reserved by</span>
                                        <p class="font-semibold text-gray-900 text-sm">#<?php echo e($reservation->user->id); ?> - <?php echo e($reservation->user->name); ?></p>
                                        <?php if($reservation->expiry_date): ?>
                                            <p class="text-xs text-amber-600 mt-1">
                                                <i class="fa-solid fa-clock mr-1"></i>
                                                Expires: <?php echo e($reservation->expiry_date->format('M d, Y')); ?>

                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <form action="<?php echo e(route('borrowings.borrow')); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="book_id" value="<?php echo e($reservation->book->bookId); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo e($reservation->user->id); ?>">
                                        <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors" onclick="return confirm('Issue this book to <?php echo e($reservation->user->name); ?>?')">
                                            <i class="fa-solid fa-book-open mr-1"></i> Borrow for #<?php echo e($reservation->user->id); ?>

                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-8 text-center text-sm text-gray-500">
                Secure Library Management System &copy; <?php echo e(date('Y')); ?>

            </footer>
        </main>

    </div>

</body>
</html>
<?php /**PATH C:\Users\User\Documents\GitHub\tarumt-librarySystem\resources\views/borrowings/index.blade.php ENDPATH**/ ?>