<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\UserApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| REST API Endpoints for Book and User modules
| Can be consumed by external applications, mobile apps, or frontend frameworks
|
*/

Route::middleware(['web'])->group(function () {

    // =====================================================================
    // BOOK API ENDPOINTS (REST)
    // =====================================================================
    // ALL endpoints require authentication (login or API token)
    // Only authenticated users (Student/Staff/Admin) can access books
    // =====================================================================
    
    Route::prefix('books')->middleware(['api_token_auth'])->group(function () {
        
        // ═════════════════════════════════════════════════════════════════
        // READ ENDPOINTS (All authenticated users can read)
        // ═════════════════════════════════════════════════════════════════
        
        // List all books with filtering
        Route::get('/', [BookApiController::class, 'index'])
            ->name('api.books.index');
        
        // Get books by status
        Route::get('/status/{status}', [BookApiController::class, 'getByStatus'])
            ->name('api.books.status');
        
        // Get books by category
        Route::get('/category/{category}', [BookApiController::class, 'getByCategory'])
            ->name('api.books.category');
        
        // Get book statistics
        Route::get('/stats/overview', [BookApiController::class, 'stats'])
            ->name('api.books.stats');

        // Get borrowing history for a book (must come before /{id})
        Route::get('/{id}/borrowing-history', [BookApiController::class, 'borrowingHistory'])
            ->name('api.books.borrowing-history');
        
        // Get single book by ID
        Route::get('/{id}', [BookApiController::class, 'show'])
            ->name('api.books.show');

        // ═════════════════════════════════════════════════════════════════
        // WRITE ENDPOINTS (Staff/Admin only)
        // ═════════════════════════════════════════════════════════════════
        Route::middleware(['check_book_permission'])->group(function () {
            
            // Create new book (Staff/Admin only)
            Route::post('/', [BookApiController::class, 'store'])
                ->name('api.books.store');
            
            // Update book (Staff/Admin only)
            Route::put('/{id}', [BookApiController::class, 'update'])
                ->name('api.books.update');
            
            // Delete book (Staff/Admin only)
            Route::delete('/{id}', [BookApiController::class, 'destroy'])
                ->name('api.books.destroy');
        });
    });

    // =====================================================================
    // USER API ENDPOINTS (REST)
    // =====================================================================
    
    Route::prefix('users')->group(function () {
        
        // List all users with pagination
        Route::get('/', [UserApiController::class, 'index'])
            ->name('api.users.index');
        
        // Get single user by ID
        Route::get('/{id}', [UserApiController::class, 'show'])
            ->name('api.users.show');
        
        // Create a new user
        Route::post('/', [UserApiController::class, 'store'])
            ->name('api.users.store');
        
        // Update a user
        Route::put('/{id}', [UserApiController::class, 'update'])
            ->name('api.users.update');
        
        // Delete a user
        Route::delete('/{id}', [UserApiController::class, 'destroy'])
            ->name('api.users.destroy');
        
        // Get users by role
        Route::get('/role/{role}', [UserApiController::class, 'getByRole'])
            ->name('api.users.role');
        
        // Search users by name or email
        Route::get('/search/query', [UserApiController::class, 'search'])
            ->name('api.users.search');
        
        // Get user statistics
        Route::get('/stats/overview', [UserApiController::class, 'stats'])
            ->name('api.users.stats');
    });

    // =====================================================================
    // RESERVATION API ENDPOINTS (REST)
    // =====================================================================
    
    Route::prefix('reservations')->group(function () {
        
        // Create a new reservation (Student only)
        Route::post('/', [\App\Http\Controllers\ReservationController::class, 'store'])
            ->name('api.reservations.store')
            ->middleware('auth');
        
        // Cancel a reservation (Student only - own reservations)
        Route::delete('/{id}', [\App\Http\Controllers\ReservationController::class, 'destroy'])
            ->name('api.reservations.destroy')
            ->middleware('auth');
        
        // Get current user's active reservations
        Route::get('/my-reservations', [\App\Http\Controllers\ReservationController::class, 'myReservations'])
            ->name('api.reservations.my-reservations')
            ->middleware('auth');
        
        // Get current user's notifications
        Route::get('/my-notifications', [\App\Http\Controllers\ReservationController::class, 'myNotifications'])
            ->name('api.reservations.my-notifications')
            ->middleware('auth');
        
        // View reservation queue for a specific book (Student can see own position)
        Route::get('/queue/{bookId}', [\App\Http\Controllers\ReservationController::class, 'viewQueue'])
            ->name('api.reservations.queue')
            ->middleware('auth');
        
        // View all reservation queues (Staff only)
        Route::get('/queues/all', [\App\Http\Controllers\ReservationController::class, 'allQueues'])
            ->name('api.reservations.all-queues')
            ->middleware('auth');
        
        // Get reservation statistics
        Route::get('/stats/overview', [\App\Http\Controllers\ReservationController::class, 'stats'])
            ->name('api.reservations.stats')
            ->middleware('auth');
    });

});
