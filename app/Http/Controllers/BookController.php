<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\BookSecurityService;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    // --- 1. SHOW THE LIST OF BOOKS ---
    public function index()
    {
        $books = Book::all(); 
        return view('books.index', compact('books'));
    }

    // --- 2. SHOW THE "ADD BOOK" FORM ---
    public function create()
    {
        return view('books.create');
    }

    // --- 3. SAVE THE NEW BOOK ---
    public function store(Request $request, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        // 2. Run Security Checks
        $cleanData = $security->validateAndSanitizeBookData($request->all());

        // 3. Save to Database
        Book::create($cleanData);

        // 4. Redirect back with success message
        return redirect()->route('books.index')->with('success', 'Book added securely!');
    }
}