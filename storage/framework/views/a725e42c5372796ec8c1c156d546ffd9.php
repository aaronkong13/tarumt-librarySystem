<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
<?php $__empty_1 = true; $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="group bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-sm font-bold text-indigo-600 bg-indigo-50 overflow-hidden">
                    <?php if($book->cover_image): ?>
                        <img src="data:image/jpeg;base64,<?php echo e(base64_encode($book->cover_image)); ?>" class="w-full h-full object-cover book-cover-clickable" onclick="openImageModal(this.src, '<?php echo e(addslashes($book->title)); ?>')" alt="cover">
                    <?php else: ?>
                        <?php echo e(substr($book->title, 0, 1)); ?>

                    <?php endif; ?>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full
                    <?php if($book->status === 'Available'): ?> bg-green-100 text-green-700 border border-green-200 <?php elseif($book->status === 'Borrowed'): ?> bg-amber-100 text-amber-700 border border-amber-200 <?php elseif($book->status === 'Reserved'): ?> bg-purple-100 text-purple-700 border border-purple-200 <?php else: ?> bg-gray-100 text-gray-700 border border-gray-200 <?php endif; ?>">
                    <i class="fa-solid fa-circle text-[6px]"></i>
                    <?php echo e(strtolower($book->status)); ?>

                </span>
            </div>
            <!-- No edit/delete for users -->
        </div>

        <div class="mb-2">
            <h3 class="text-base font-semibold text-gray-900 leading-snug"><?php echo e($book->title); ?></h3>
            <p class="text-xs text-gray-600 mt-1"><?php echo e($book->author); ?></p>
        </div>

        <div class="flex items-center justify-between pt-4 mt-2 border-t border-gray-100">
            <div class="flex items-center gap-2">
                <span class="text-[11px] px-2 py-1 rounded-md bg-gray-100 text-gray-700"><?php echo e($book->category); ?></span>
            </div>
            <span class="text-[11px] text-gray-500 font-mono"><?php echo e($book->isbn); ?></span>
        </div>

        
        <?php if(Auth::user()->isStudent() && $book->status === 'Borrowed'): ?>
            <?php
                // 检查用户是否已经预订了这本书
                $hasReserved = \App\Models\Reservation::where('user_id', Auth::id())
                    ->where('book_id', $book->bookId)
                    ->whereIn('status', ['waiting', 'notified'])
                    ->exists();
            ?>
            
            <div class="mt-4">
                <?php if($hasReserved): ?>
                    
                    <button disabled class="w-full px-4 py-2 text-sm bg-yellow-500 text-white rounded-lg cursor-not-allowed font-medium">
                        <i class="fa-solid fa-clock mr-1"></i> Pending
                    </button>
                <?php else: ?>
                    
                    <form action="<?php echo e(route('reservations.store')); ?>" method="POST" class="reserve-form" onsubmit="return handleReserveSubmit(this, '<?php echo e($book->title); ?>')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="book_id" value="<?php echo e($book->bookId); ?>">
                        <button type="submit" class="reserve-btn w-full px-4 py-2 text-sm bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 transition-colors border border-purple-200 font-medium">
                            <span class="btn-text">
                                <i class="fa-solid fa-bookmark mr-1"></i> Reserve This Book
                            </span>
                            <span class="btn-loading hidden">
                                <i class="fa-solid fa-clock mr-1"></i> Pending
                            </span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-span-full">
        <div class="py-12 text-center text-gray-500 bg-white border border-dashed border-gray-200 rounded-2xl">
            <i class="fa-solid fa-box-open text-4xl mb-3 text-gray-300"></i>
            <p class="text-lg font-medium">No books found</p>
        </div>
    </div>
<?php endif; ?>
</div>

<div class="mt-6 pagination">
    <?php echo e($books->links()); ?>

</div>

<script>
function handleReserveSubmit(form, bookTitle) {
    const button = form.querySelector('.reserve-btn');
    const btnText = button.querySelector('.btn-text');
    const btnLoading = button.querySelector('.btn-loading');
    
    // 禁用按钮防止重复提交并改变颜色
    button.disabled = true;
    button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
    button.classList.add('bg-yellow-500', 'cursor-not-allowed');
    
    // 显示加载状态
    btnText.classList.add('hidden');
    btnLoading.classList.remove('hidden');
    
    // 如果3秒后还没响应，显示提示
    const timeout = setTimeout(() => {
        // 表单仍在提交中，显示处理中的消息
        console.log('Still processing reservation...');
    }, 3000);
    
    // 允许表单正常提交
    return true;
}

// 页面加载时检查是否有成功消息
document.addEventListener('DOMContentLoaded', function() {
    // 如果有成功消息，滚动到顶部让用户看到
    const successMsg = document.querySelector('.bg-green-50');
    if (successMsg) {
        successMsg.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // 5秒后自动淡出消息
        setTimeout(() => {
            successMsg.style.transition = 'opacity 0.5s';
            successMsg.style.opacity = '0';
            setTimeout(() => successMsg.remove(), 500);
        }, 5000);
    }
});
</script>
<?php /**PATH C:\Users\User\Documents\GitHub\tarumt-librarySystem\resources\views/layouts/book-cards.blade.php ENDPATH**/ ?>