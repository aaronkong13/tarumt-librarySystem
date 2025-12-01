<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book; // Import the Book Model
use App\Services\BookSecurityService; // Import Security Service

class BookController extends Controller
{
    // --- 1. SHOW THE LIST OF BOOKS (The missing part) ---
    public function index()
    {
        // Fetch all books from database
        $books = Book::all(); 

        // Send them to the view
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
        // 1. Simulate a Staff User (For testing)
        $testUser = (object) ['role' => 'staff', 'id' => 1]; 

        // 2. Run Security Checks
        $security->enforceStaffAccess($testUser); // Check Access
        $cleanData = $security->validateAndSanitizeBookData($request->all()); // Check Input

        // 3. Save to Database using the Model
        // (Ensure you have 'title', 'isbn', 'year' in your $fillable property in Book.php)
        Book::create($cleanData);

        // 4. Redirect back with success message
        return redirect()->route('books.index')->with('success', 'Book added securely!');
    }
}