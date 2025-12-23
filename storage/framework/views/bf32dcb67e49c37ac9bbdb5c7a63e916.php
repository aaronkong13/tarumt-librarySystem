<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Queue - <?php echo e($book->title); ?> - BookHub</title>
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
                    <h2 class="text-xl font-bold text-gray-900">Reservation Queue</h2>
                    <p class="text-sm text-gray-500"><?php echo e($book->title); ?></p>
                </div>
                <a href="<?php echo e(route('reservations.all-queues')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>All Queues</span>
                </a>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <!-- Book Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <?php if($book->cover_image): ?>
                                <img src="data:image/jpeg;base64,<?php echo e(base64_encode($book->cover_image)); ?>" 
                                     class="w-24 h-32 object-cover rounded-lg shadow" 
                                     alt="<?php echo e($book->title); ?>">
                            <?php else: ?>
                                <div class="w-24 h-32 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg shadow flex items-center justify-center">
                                    <i class="fa-solid fa-book text-white text-4xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo e($book->title); ?></h3>
                            <p class="text-gray-600 mb-3">
                                <i class="fa-solid fa-user-pen mr-2"></i><?php echo e($book->author); ?>

                                <span class="mx-2">•</span>
                                <i class="fa-solid fa-barcode mr-2"></i><?php echo e($book->isbn); ?>

                            </p>
                            <div class="flex items-center gap-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium
                                    <?php if($book->status === 'Available'): ?> bg-green-100 text-green-700 border border-green-200
                                    <?php elseif($book->status === 'Borrowed'): ?> bg-amber-100 text-amber-700 border border-amber-200
                                    <?php elseif($book->status === 'Reserved'): ?> bg-purple-100 text-purple-700 border border-purple-200
                                    <?php else: ?> bg-gray-100 text-gray-700 border border-gray-200 <?php endif; ?>">
                                    <i class="fa-solid fa-circle text-[8px]"></i>
                                    <?php echo e($book->status); ?>

                                </span>
                                <span class="text-sm text-gray-600">
                                    <i class="fa-solid fa-users mr-1"></i>
                                    <?php echo e(count($queue)); ?> <?php echo e(count($queue) === 1 ? 'person' : 'people'); ?> in queue
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Queue List -->
                <?php if(count($queue) > 0): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-900">Reservation Queue (FIFO Order)</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php $__currentLoopData = $queue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="p-6 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4 flex-1">
                                            <!-- Position Badge -->
                                            <div class="flex-shrink-0">
                                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold
                                                    <?php if($reservation['queue_position'] === 1): ?> bg-indigo-100 text-indigo-700
                                                    <?php elseif($reservation['queue_position'] === 2): ?> bg-blue-100 text-blue-700
                                                    <?php elseif($reservation['queue_position'] === 3): ?> bg-purple-100 text-purple-700
                                                    <?php else: ?> bg-gray-100 text-gray-700 <?php endif; ?>">
                                                    #<?php echo e($reservation['queue_position']); ?>

                                                </div>
                                            </div>

                                            <!-- User Info -->
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-semibold text-gray-900"><?php echo e($reservation['user_name']); ?></h4>
                                                    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full
                                                        <?php if($reservation['status'] === 'waiting'): ?> bg-blue-100 text-blue-700 border border-blue-200
                                                        <?php elseif($reservation['status'] === 'notified'): ?> bg-green-100 text-green-700 border border-green-200
                                                        <?php else: ?> bg-gray-100 text-gray-700 border border-gray-200 <?php endif; ?>">
                                                        <i class="fa-solid fa-circle text-[6px]"></i>
                                                        <?php echo e($reservation['status_label']); ?>

                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600">
                                                    <i class="fa-solid fa-envelope mr-1"></i><?php echo e($reservation['user_email']); ?>

                                                    <span class="mx-2">•</span>
                                                    <i class="fa-solid fa-id-badge mr-1"></i>User ID: <?php echo e($reservation['user_id']); ?>

                                                </p>
                                            </div>
                                        </div>

                                        <!-- Timeline -->
                                        <div class="text-right text-sm">
                                            <p class="text-gray-600 mb-1">
                                                <i class="fa-solid fa-calendar-plus mr-1"></i>
                                                Reserved: <?php echo e(\Carbon\Carbon::parse($reservation['reservation_date'])->format('M d, Y')); ?>

                                            </p>
                                            <?php if($reservation['notified_at']): ?>
                                                <p class="text-green-600 mb-1">
                                                    <i class="fa-solid fa-bell mr-1"></i>
                                                    Notified: <?php echo e(\Carbon\Carbon::parse($reservation['notified_at'])->format('M d, Y H:i')); ?>

                                                </p>
                                            <?php endif; ?>
                                            <?php if($reservation['expiry_date']): ?>
                                                <p class="<?php echo e($reservation['remaining_days'] <= 1 ? 'text-red-600' : 'text-gray-600'); ?>">
                                                    <i class="fa-solid fa-clock mr-1"></i>
                                                    Expires: <?php echo e(\Carbon\Carbon::parse($reservation['expiry_date'])->format('M d, Y')); ?>

                                                    <?php if($reservation['remaining_days'] !== null): ?>
                                                        (<?php echo e($reservation['remaining_days']); ?> day(s) left)
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-users-slash text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No Reservations</h3>
                        <p class="text-gray-600">There are no active reservations for this book.</p>
                    </div>
                <?php endif; ?>

            </div>

        </main>

    </div>

</body>
</html>
<?php /**PATH C:\xampp\ass\htdocs\tarumt-librarySystem\resources\views/reservations/queue.blade.php ENDPATH**/ ?>