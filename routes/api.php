<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ReservationApiController;
use App\Http\Controllers\Api\BorrowingApiController;

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

// =====================================================================
// REMINDER API ENDPOINTS (Admin/Staff Only)
// =====================================================================
Route::prefix('reminders')->middleware(['auth:sanctum', 'check.staff'])->group(function () {
    Route::get('/statistics', [BorrowingApiController::class, 'reminderStatistics'])->name('api.reminders.statistics');
    Route::get('/preview', [BorrowingApiController::class, 'previewReminders'])->name('api.reminders.preview');
    Route::get('/configuration', [BorrowingApiController::class, 'reminderConfiguration'])->name('api.reminders.configuration');
    Route::post('/send', [BorrowingApiController::class, 'sendReminders'])->name('api.reminders.send');
});

// =====================================================================
// BORROWING API ENDPOINTS (REST)
// =====================================================================
Route::prefix('borrowings')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/borrow', [\App\Http\Controllers\BorrowingController::class, 'borrow']);
    Route::post('/{borrowing}/return', [\App\Http\Controllers\BorrowingController::class, 'return']);
    Route::post('/{borrowing}/renew', [\App\Http\Controllers\BorrowingController::class, 'renew']);
    Route::get('/history', [\App\Http\Controllers\BorrowingController::class, 'myHistory']);
    Route::post('/reserve', [\App\Http\Controllers\BorrowingController::class, 'reserve']);
    Route::delete('/reservations/{reservation}', [\App\Http\Controllers\BorrowingController::class, 'cancelReservation']);
    Route::get('/books/{book}/availability', [\App\Http\Controllers\BorrowingController::class, 'checkAvailability']);
    Route::get('/overdue', [\App\Http\Controllers\BorrowingController::class, 'overdueList'])->middleware('check.staff');
});

// =====================================================================
// FINE API ENDPOINTS (REST)
// =====================================================================
Route::prefix('fines')->middleware(['auth:sanctum'])->group(function () {
    // List fines
    Route::get('/', [BorrowingApiController::class, 'fines'])->name('api.fines.index');
    Route::get('/check-unpaid', [BorrowingApiController::class, 'checkUnpaidFines'])->name('api.fines.check-unpaid');
    Route::get('/statistics', [BorrowingApiController::class, 'fineStatistics'])->name('api.fines.statistics')->middleware('check.staff');
    Route::get('/report', [BorrowingApiController::class, 'fineReport'])->name('api.fines.report')->middleware('check.staff');
    Route::get('/user/{userId?}/summary', [BorrowingApiController::class, 'userFineSummary'])->name('api.fines.user-summary');
    Route::get('/{id}', [BorrowingApiController::class, 'showFine'])->name('api.fines.show');

    // Actions
    Route::post('/pay-all', [BorrowingApiController::class, 'payAllFines'])->name('api.fines.pay-all');
    Route::post('/process-overdue', [BorrowingApiController::class, 'processOverdueFines'])->name('api.fines.process-overdue')->middleware('check.staff');
    Route::post('/{id}/pay', [BorrowingApiController::class, 'payFine'])->name('api.fines.pay');
    Route::post('/{id}/waive', [BorrowingApiController::class, 'waiveFine'])->name('api.fines.waive')->middleware('check.staff');
});

// =====================================================================
// CROSS-MODULE API ENDPOINTS (No Authentication Required)
// These endpoints are for internal cross-module communication
// Safe because they're read-only and only accessible from server-side
// =====================================================================

// BORROWING STATS API (for Book Management module)
Route::get('/borrowings/books/{bookId}/stats', [BorrowingApiController::class, 'bookStats'])
    ->name('api.borrowings.book-stats');

// USER BORROWING HISTORY API (for User Management module)
Route::get('/borrowings/users/{userId}/history', [BorrowingApiController::class, 'getUserBorrowings'])
    ->name('api.borrowings.user-history');

// USER RESERVATION HISTORY API (for User Management module)
Route::get('/reservations/users/{userId}/history', [ReservationApiController::class, 'getUserReservations'])
    ->name('api.reservations.user-history');

// BOOK API ENDPOINTS (for Borrowing module)
Route::prefix('books')->group(function () {
    // Get available books
    Route::get('/available', [BookApiController::class, 'getAvailableBooks'])
        ->name('api.books.available');

    // Get single book by ID
    Route::get('/{id}', [BookApiController::class, 'show'])
        ->name('api.books.show-internal');

    // Update book (for status changes from borrowing module)
    Route::put('/{id}', [BookApiController::class, 'update'])
        ->name('api.books.update-internal');
});

// USER API ENDPOINTS (for Borrowing module)
Route::get('/users/{id}', [UserApiController::class, 'show'])
    ->name('api.users.show-internal');

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

        // Get user's reservations
        Route::get('/{id}/reservations', [UserApiController::class, 'getUserReservations'])
            ->name('api.users.reservations');

        // Get user's fines
        Route::get('/{id}/fines', [UserApiController::class, 'getUserFines'])
            ->name('api.users.fines');
    });

    // =====================================================================
    // RESERVATION API ENDPOINTS (REST)
    // =====================================================================

    Route::prefix('reservations')->group(function () {

        // Create a new reservation (Student only)
        Route::post('/', [ReservationApiController::class, 'store'])
            ->name('api.reservations.store')
            ->middleware('auth');

        // Cancel a reservation (Student only - own reservations)
        Route::delete('/{id}', [ReservationApiController::class, 'destroy'])
            ->name('api.reservations.destroy')
            ->middleware('auth');

        // Get current user's active reservations
        Route::get('/my-reservations', [ReservationApiController::class, 'myReservations'])
            ->name('api.reservations.my-reservations')
            ->middleware('auth');

        // Get current user's notifications
        Route::get('/my-notifications', [ReservationApiController::class, 'myNotifications'])
            ->name('api.reservations.my-notifications')
            ->middleware('auth');

        // Check reservation status for a book
        Route::get('/check/{bookId}', [ReservationApiController::class, 'checkStatus'])
            ->name('api.reservations.check')
            ->middleware('auth');

        // View reservation queue for a specific book (Student can see own position)
        Route::get('/queue/{bookId}', [ReservationApiController::class, 'viewQueue'])
            ->name('api.reservations.queue')
            ->middleware('auth');

        // View all reservation queues (Staff only)
        Route::get('/queues/all', [ReservationApiController::class, 'allQueues'])
            ->name('api.reservations.all-queues')
            ->middleware('auth');

        // Get reservation statistics
        Route::get('/stats/overview', [ReservationApiController::class, 'stats'])
            ->name('api.reservations.stats')
            ->middleware('auth');

        // Get reservations ready for pickup (Staff only)
        Route::get('/ready-for-pickup', [ReservationApiController::class, 'readyForPickup'])
            ->name('api.reservations.ready-for-pickup')
            ->middleware('auth');
    });

});
