<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - BookHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        
        <!-- Card Container -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            
            <!-- Header with gradient -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6 text-center">
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-envelope-circle-check text-indigo-600 text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-white">Verify Your Email</h1>
                <p class="text-indigo-100 text-sm mt-2">Please verify your email address to continue</p>
            </div>

            <!-- Body -->
            <div class="p-8">

                <?php if(session('success')): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-circle-check text-green-500 mr-3"></i>
                            <p class="text-green-800 font-medium"><?php echo e(session('success')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-circle-exclamation text-red-500 mr-3"></i>
                            <p class="text-red-800 font-medium"><?php echo e(session('error')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(session('info')): ?>
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                        <div class="flex items-center">
                            <i class="fa-solid fa-circle-info text-blue-500 mr-3"></i>
                            <p class="text-blue-800 font-medium"><?php echo e(session('info')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- User Info -->
                <div class="mb-6 bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center">
                        <?php if(Auth::user()->profile_image): ?>
                            <img src="data:image/jpeg;base64,<?php echo e(base64_encode(Auth::user()->profile_image)); ?>" 
                                 alt="<?php echo e(Auth::user()->name); ?>" 
                                 class="w-12 h-12 rounded-full object-cover mr-4">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold mr-4">
                                <?php echo e(strtoupper(substr(Auth::user()->name, 0, 2))); ?>

                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-semibold text-gray-900"><?php echo e(Auth::user()->name); ?></p>
                            <p class="text-sm text-gray-600"><?php echo e(Auth::user()->email); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <div class="mb-6 text-center">
                    <p class="text-gray-700 mb-4">
                        <i class="fa-solid fa-paper-plane text-indigo-500 mr-2"></i>
                        A verification email has been sent to your email address.
                    </p>
                    <p class="text-sm text-gray-600 mb-4">
                        Please check your inbox and click on the verification link to activate your account.
                    </p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                        <p class="text-xs text-yellow-800">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            <strong>Note:</strong> The verification link will expire in 60 minutes. 
                            Check your spam folder if you don't see the email.
                        </p>
                    </div>
                </div>

                <!-- Resend Button -->
                <form method="POST" action="<?php echo e(route('verification.resend')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transition-all transform hover:scale-[1.02] flex items-center justify-center">
                        <i class="fa-solid fa-rotate-right mr-2"></i>
                        Resend Verification Email
                    </button>
                </form>

            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-200">
                <p class="text-xs text-gray-500 text-center">
                    <i class="fa-solid fa-shield-halved mr-1"></i>
                    Your account security is our priority
                </p>
            </div>

        </div>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Having trouble? 
                <a href="<?php echo e(route('login')); ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold underline">
                    Contact Support
                </a>
            </p>
        </div>

    </div>

</body>
</html>
<?php /**PATH C:\Users\User\Documents\GitHub\tarumt-librarySystem\resources\views/auth/verify-email.blade.php ENDPATH**/ ?>