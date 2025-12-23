<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        .pulse-ring {
            animation: pulse-ring 2s infinite;
        }
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
                    <h2 class="text-xl font-bold text-gray-900">My Notifications</h2>
                    <p class="text-sm text-gray-500">Books ready for pickup</p>
                </div>
                <a href="<?php echo e(route('reservations.my')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fa-solid fa-bookmark"></i>
                    <span>View All Reservations</span>
                </a>
            </header>

            <!-- Main Content -->
            <div class="p-8">

                <?php if(session('success')): ?>
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                        <i class="fa-solid fa-circle-check mr-2"></i> <?php echo e(session('success')); ?>

                    </div>
                <?php endif; ?>

                <?php if(count($notifications) > 0): ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="bg-white rounded-xl shadow-sm border-2 <?php echo e($notification['is_expired'] ? 'border-red-200' : 'border-green-200'); ?> p-6 hover:shadow-md transition-shadow relative overflow-hidden">
                                
                                <?php if(!$notification['is_expired']): ?>
                                    <div class="absolute top-0 right-0 w-20 h-20 bg-green-400 opacity-10 rounded-bl-full"></div>
                                <?php endif; ?>

                                <div class="flex items-start gap-4">
                                    <!-- Book Cover -->
                                    <div class="flex-shrink-0">
                                        <?php if($notification['book_cover']): ?>
                                            <img src="data:image/jpeg;base64,<?php echo e(base64_encode($notification['book_cover'])); ?>" 
                                                 class="w-20 h-28 object-cover rounded-lg shadow-md" 
                                                 alt="<?php echo e($notification['book_title']); ?>">
                                        <?php else: ?>
                                            <div class="w-20 h-28 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg shadow-md flex items-center justify-center">
                                                <i class="fa-solid fa-book text-white text-3xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 leading-tight">
                                                <?php echo e($notification['book_title']); ?>

                                            </h3>
                                            <?php if(!$notification['is_expired']): ?>
                                                <span class="flex-shrink-0 w-3 h-3 bg-green-500 rounded-full pulse-ring"></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 mb-3">
                                            <i class="fa-solid fa-user-pen mr-1"></i>
                                            <?php echo e($notification['book_author']); ?>

                                        </p>

                                        <div class="space-y-2 text-sm mb-4">
                                            <div class="flex items-center gap-2 text-gray-600">
                                                <i class="fa-solid fa-bell w-4"></i>
                                                <span>Notified: <?php echo e($notification['notified_at']); ?></span>
                                            </div>
                                            <div class="flex items-center gap-2 <?php echo e($notification['is_expired'] ? 'text-red-600' : ($notification['remaining_days'] <= 1 ? 'text-orange-600' : 'text-gray-600')); ?>">
                                                <i class="fa-solid fa-calendar-xmark w-4"></i>
                                                <span>Expires: <?php echo e($notification['expiry_date']); ?></span>
                                            </div>
                                        </div>

                                        <?php if($notification['is_expired']): ?>
                                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg mb-3">
                                                <p class="text-sm text-red-800">
                                                    <i class="fa-solid fa-circle-exclamation mr-1"></i>
                                                    <strong>Expired!</strong> This reservation has expired. The book may have been offered to the next person in queue.
                                                </p>
                                            </div>
                                        <?php else: ?>
                                            <div class="p-3 <?php echo e($notification['remaining_days'] <= 1 ? 'bg-orange-50 border-orange-200' : 'bg-green-50 border-green-200'); ?> border rounded-lg mb-3">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-solid fa-clock <?php echo e($notification['remaining_days'] <= 1 ? 'text-orange-600' : 'text-green-600'); ?>"></i>
                                                    <p class="text-sm <?php echo e($notification['remaining_days'] <= 1 ? 'text-orange-800' : 'text-green-800'); ?> font-medium">
                                                        <?php echo e($notification['remaining_days']); ?> day(s) remaining to collect
                                                    </p>
                                                </div>
                                            </div>

                                            <a href="<?php echo e(route('books.catalog')); ?>" class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                                <i class="fa-solid fa-book-open mr-2"></i>
                                                Go to Books & Borrow
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-16">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-bell-slash text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No Notifications</h3>
                        <p class="text-gray-600 mb-6">You don't have any book availability notifications at the moment.</p>
                        <a href="<?php echo e(route('reservations.my')); ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            <i class="fa-solid fa-bookmark"></i>
                            <span>View My Reservations</span>
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        </main>

    </div>

</body>
</html>
<?php /**PATH C:\xampp\ass\htdocs\tarumt-librarySystem\resources\views/reservations/my-notifications.blade.php ENDPATH**/ ?>