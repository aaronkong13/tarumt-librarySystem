

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
        
        <a href="/dashboard" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'dashboard' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-house w-6"></i>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        
        <?php if(in_array(Auth::user()->role, ['Staff', 'Admin'])): ?>
        <a href="<?php echo e(route('books.index')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'books' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-book w-6"></i>
            <span class="font-medium text-sm">Books Management</span>
        </a>
        <?php else: ?>
        <a href="<?php echo e(route('books.catalog')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'books' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-book w-6"></i>
            <span class="font-medium text-sm">Books List</span>
        </a>
        <?php endif; ?>

        
        <?php if(in_array(Auth::user()->role, ['Staff', 'Admin'])): ?>
        <a href="<?php echo e(route('borrowings.index')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'borrowings' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-hand-holding w-6"></i>
            <span class="font-medium text-sm">Borrow & Return</span>
        </a>
        <?php endif; ?>

        
        <?php if(in_array(Auth::user()->role, ['Staff', 'Admin'])): ?>
        <a href="<?php echo e(route('users.index')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'users' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-users w-6"></i>
            <span class="font-medium text-sm">User Management</span>
        </a>
        <?php endif; ?>

        
        <?php if(Auth::user()->isStudent()): ?>
        <a href="<?php echo e(route('reservations.my')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'reservations' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-bookmark w-6"></i>
            <span class="font-medium text-sm">My Reservations</span>
        </a>
        <?php endif; ?>

        
        <?php if(in_array(Auth::user()->role, ['Staff', 'Admin'])): ?>
        <a href="<?php echo e(route('reservations.all-queues')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'reservations' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-clipboard-list w-6"></i>
            <span class="font-medium text-sm">Reservation Queues</span>
        </a>
        <?php endif; ?>

        
        <?php if(Route::has('borrowings.history')): ?>
        <a href="<?php echo e(route('borrowings.history')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'history' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-clock-rotate-left w-6"></i>
            <span class="font-medium text-sm">History</span>
        </a>
        <?php endif; ?>

        
        <?php if(Route::has('fines.index')): ?>
        <a href="<?php echo e(route('fines.index')); ?>" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'fines' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-money-bill w-6"></i>
            <span class="font-medium text-sm">Fines</span>
        </a>
        <?php endif; ?>

        
        <a href="#" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'reports' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-chart-simple w-6"></i>
            <span class="font-medium text-sm">Reports</span>
        </a>

        
        <a href="#" 
           class="flex items-center px-4 py-3 rounded-xl transition-colors <?php echo e($active === 'settings' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/50' : 'text-gray-400 hover:text-white hover:bg-gray-800'); ?>">
            <i class="fa-solid fa-gear w-6"></i>
            <span class="font-medium text-sm">Settings</span>
        </a>
    </nav>

    
    <div class="p-4 border-t border-gray-800">
        <a href="<?php echo e(route('profile.show')); ?>" class="block">
            <div class="bg-[#1E293B] rounded-xl p-3 flex items-center gap-3 hover:bg-[#2D3B52] transition-colors cursor-pointer">
                <?php if(Auth::user()->profile_image): ?>
                    <img src="data:image/jpeg;base64,<?php echo e(base64_encode(Auth::user()->profile_image)); ?>" 
                         alt="<?php echo e(Auth::user()->name); ?>" 
                         class="w-10 h-10 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold">
                        <?php echo e(strtoupper(substr(Auth::user()->name, 0, 2))); ?>

                    </div>
                <?php endif; ?>
                <div class="flex-1">
                    <p class="text-sm font-semibold truncate"><?php echo e(Auth::user()->name); ?></p>
                    <p class="text-xs text-gray-400"><?php echo e(ucfirst(Auth::user()->role)); ?></p>
                </div>
                <form action="<?php echo e(route('logout')); ?>" method="POST" onclick="event.stopPropagation();">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-right-from-bracket text-lg"></i>
                    </button>
                </form>
            </div>
        </a>
    </div>
</aside>
<?php /**PATH C:\Users\Cheng\Documents\GitHub\tarumt-librarySystem\resources\views/layouts/sidebar.blade.php ENDPATH**/ ?>