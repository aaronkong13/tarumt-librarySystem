<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// --- Book Management Routes ---

// 1. Show the "Add Book" form
Route::get('/books/create', [BookController::class, 'create'])->name('books.create');

// 2. Handle the form submission (Save data)
Route::post('/books/store', [BookController::class, 'store'])->name('books.store');

// 3. Show the list of books
Route::get('/books', [BookController::class, 'index'])->name('books.index');