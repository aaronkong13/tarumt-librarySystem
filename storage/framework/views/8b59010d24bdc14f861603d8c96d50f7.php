<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - BookHub</title>
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
        <?php echo $__env->make('layouts.sidebar', ['active' => 'reservations'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="flex-1 md:ml-64 relative">

            <!-- Header -->
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">My Reservations</h2>
                    <p class="text-sm text-gray-500">Track your book reservations</p>
                </div>
                <a href="<?php echo e(route('reservations.notifications')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fa-solid fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <?php if(session('success')): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                        <i class="fa-solid fa-circle-check mr-2"></i> <?php echo e(session('success')); ?>

                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i> <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?>

                <?php if(count($reservations) > 0): ?>
                    <div class="grid grid-cols-1 gap-6">
                        <?php $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900"><?php echo e($reservation['book_title']); ?></h3>
                                            <span class="inline-flex items-center gap-1.5 text-xs px-3 py-1 rounded-full
                                                <?php if($reservation['status'] === 'waiting'): ?> bg-blue-100 text-blue-700 border border-blue-200
                                                <?php elseif($reservation['status'] === 'notified'): ?> bg-green-100 text-green-700 border border-green-200 animate-pulse
                                                <?php else: ?> bg-gray-100 text-gray-700 border border-gray-200 <?php endif; ?>">
                                                <i class="fa-solid fa-circle text-[6px]"></i>
                                                <?php echo e($reservation['status_label']); ?>

                                            </span>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 mb-3">
                                            <i class="fa-solid fa-user-pen mr-1"></i>
                                            <?php echo e($reservation['book_author']); ?>

                                            <span class="mx-2">•</span>
                                            <i class="fa-solid fa-barcode mr-1"></i>
                                            <?php echo e($reservation['book_isbn']); ?>

                                        </p>

                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                                                    <i class="fa-solid fa-list-ol text-indigo-600 text-xs"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Queue Position</p>
                                                    <p class="font-semibold text-gray-900">#<?php echo e($reservation['queue_position']); ?></p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                                                    <i class="fa-solid fa-calendar-days text-purple-600 text-xs"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Reserved On</p>
                                                    <p class="font-semibold text-gray-900"><?php echo e($reservation['reservation_date']); ?></p>
                                                </div>
                                            </div>

                                            <?php if($reservation['status'] === 'notified' && $reservation['remaining_days'] !== null): ?>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-lg <?php echo e($reservation['remaining_days'] <= 1 ? 'bg-red-50' : 'bg-green-50'); ?> flex items-center justify-center">
                                                        <i class="fa-solid fa-clock <?php echo e($reservation['remaining_days'] <= 1 ? 'text-red-600' : 'text-green-600'); ?> text-xs"></i>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500">Time Remaining</p>
                                                        <p class="font-semibold <?php echo e($reservation['remaining_days'] <= 1 ? 'text-red-600' : 'text-green-600'); ?>">
                                                            <?php echo e($reservation['remaining_days']); ?> day(s)
                                                        </p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if($reservation['status'] === 'notified'): ?>
                                            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                                <p class="text-sm text-green-800">
                                                    <i class="fa-solid fa-circle-check mr-1"></i>
                                                    <strong>Book is Available!</strong> You have <?php echo e($reservation['remaining_days']); ?> day(s) to borrow this book before your reservation expires.
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex flex-col gap-2 ml-4">
                                        <?php if($reservation['can_cancel']): ?>
                                            <form action="<?php echo e(route('reservations.destroy', $reservation['id'])); ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="px-4 py-2 text-sm bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors border border-red-200">
                                                    <i class="fa-solid fa-xmark mr-1"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-bookmark text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Active Reservations</h3>
                        <p class="text-gray-600 mb-6">You don't have any active book reservations at the moment.</p>
                        <a href="<?php echo e(route('books.catalog')); ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            <i class="fa-solid fa-book"></i>
                            <span>Browse Books</span>
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        </main>

    </div>

</body>
</html>
<?php /**PATH C:\Users\User\Documents\GitHub\tarumt-librarySystem\resources\views/reservations/my-reservations.blade.php ENDPATH**/ ?>