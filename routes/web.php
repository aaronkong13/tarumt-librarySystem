<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\BookStateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// --- Authentication Routes ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showPasswordResetRequestForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showPasswordResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // User-facing catalog (students; card layout, no staff actions)
    Route::get('/books/catalog', [BookController::class, 'catalog'])->name('books.catalog');

    // --- User Management Routes (Staff/Admin Only) ---
    Route::middleware('check.staff')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
    });

    // --- Profile Routes (Personal profile editing) ---
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // --- User Profile Routes (must be after /users/create) ---
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.deactivate');

    // --- Book Management Routes---
    Route::middleware('check.staff')->group(function () {
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    });

    // --- Borrowing & Reservation Routes ---
    Route::prefix('borrowings')->group(function () {
        Route::get('/', [BorrowingController::class, 'index'])->name('borrowings.index');
        Route::post('/borrow', [BorrowingController::class, 'borrow'])->name('borrowings.borrow');
        Route::post('/{borrowing}/return', [BorrowingController::class, 'return'])->name('borrowings.return');
        Route::post('/{borrowing}/renew', [BorrowingController::class, 'renew'])->name('borrowings.renew');
        Route::get('/history', [BorrowingController::class, 'myHistory'])->name('borrowings.history');
        Route::get('/fines', [BorrowingController::class, 'myFines'])->name('borrowings.fines');
        Route::post('/fines/{fine}/pay', [BorrowingController::class, 'payFine'])->name('fines.pay');

        // Reservations
        Route::post('/reserve', [BorrowingController::class, 'reserve'])->name('borrowings.reserve');
        Route::delete('/reservations/{reservation}', [BorrowingController::class, 'cancelReservation'])->name('reservations.cancel');

        // API
        Route::get('/books/{book}/availability', [BorrowingController::class, 'checkAvailability'])->name('borrowings.availability');

        // Staff only
        Route::middleware('check.staff')->group(function () {
            Route::get('/overdue', [BorrowingController::class, 'overdueList'])->name('borrowings.overdue');
        });
    });

    // --- Design Pattern Routes (State & Observer Patterns) ---
    Route::prefix('state-borrowings')->group(function () {
        Route::get('/book/{book}/state', [BookStateController::class, 'getBookState'])->name('state.book.info');
        Route::post('/borrow', [BookStateController::class, 'borrow'])->name('state.borrow');
        Route::post('/{borrowing}/return', [BookStateController::class, 'return'])->name('state.return');
        Route::post('/{borrowing}/renew', [BookStateController::class, 'renew'])->name('state.renew');
        Route::post('/reserve', [BookStateController::class, 'reserve'])->name('state.reserve');
        Route::delete('/reservations/{reservation}', [BookStateController::class, 'cancelReservation'])->name('state.reservations.cancel');

        // Scheduler endpoints (for cron jobs)
        Route::middleware('check.staff')->group(function () {
            Route::post('/reservations/process-expired', [BookStateController::class, 'processExpiredReservations'])->name('state.reservations.process');
            Route::post('/reservations/notify-expiring', [BookStateController::class, 'notifyExpiringReservations'])->name('state.reservations.notify');
        });
    });
});
