<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
<<<<<<< Updated upstream
use App\Models\Book; // Import the Book Model
use App\Services\BookSecurityService; // Import Security Service
=======
use App\Models\Book;
use App\Services\BookSecurityService; 
use App\Services\BookSearch\BookSearchContext;
use App\Services\BookSearch\Strategies\CategoryFilter;
use App\Services\BookSearch\Strategies\KeywordFilter;
use App\Services\BookSearch\Strategies\SortStrategy;
use App\Services\BookSearch\Strategies\StatusFilter;
use App\Services\BookSearch\Strategies\YearRangeFilter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
<<<<<<< Updated upstream
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes

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

<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
    // --- 2. SHOW THE "ADD BOOK" FORM ---
    public function create()
    {
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
    // --- LIST ---
    public function index(Request $request, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        $query = Book::query();
        $query = $this->searchContext->apply($query, $request);

        $books = $query
            ->paginate(10) 
            ->appends($request->query());

        return view('books.index', [
            'books' => $books,
            'filters' => $request->only(['q', 'status', 'category', 'year_from', 'year_to', 'sort']),
            'categories' => Book::distinct()->pluck('category')->sort()->values(),
        ]);
    }

    // --- CREATE ---
    public function create(BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

>>>>>>> Stashed changes
        return view('books.create');
    }

    // --- 3. SAVE THE NEW BOOK ---
    public function store(Request $request, BookSecurityService $security)
    {
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
        // 1. Simulate a Staff User (For testing)
        $testUser = (object) ['role' => 'staff', 'id' => 1]; 
=======
        $security->enforceStaffAccess(Auth::user());
>>>>>>> Stashed changes
=======
        $security->enforceStaffAccess(Auth::user());
>>>>>>> Stashed changes
=======
        $security->enforceStaffAccess(Auth::user());
>>>>>>> Stashed changes

        // 2. Run Security Checks
        $security->enforceStaffAccess($testUser); // Check Access
        $cleanData = $security->validateAndSanitizeBookData($request->all()); // Check Input

        // 3. Save to Database using the Model
        // (Ensure you have 'title', 'isbn', 'year' in your $fillable property in Book.php)
        Book::create($cleanData);

<<<<<<< Updated upstream
        // 4. Redirect back with success message
        return redirect()->route('books.index')->with('success', 'Book added securely!');
=======
        return redirect()->route('books.index')->with('success', 'Book added securely.');
    }

    // --- EDIT ---
    public function edit(Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        return view('books.edit', compact('book'));
    }

    // --- UPDATE ---
    public function update(Request $request, Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:0', 'max:3000'],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:Available,Borrowed,Lost,Damaged'],
            'cover' => ['nullable', 'file', 'mimes:jpeg,png,gif', 'max:2048'],
        ]);

        $cleanData = $security->validateAndSanitizeBookData($validated);

        if ($request->hasFile('cover')) {
            $security->validateCoverImage($request->file('cover'));
            // Replace with new image BLOB (old one is automatically overwritten)
            $cleanData['cover_image'] = file_get_contents($request->file('cover')->getRealPath());
        }

        $book->update($cleanData);

        return redirect()->route('books.index')->with('success', 'Book updated.');
    }

    // --- DELETE (SOFT) ---
    public function destroy(Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        if ($book->status === 'Borrowed') {
            return back()->withErrors(['message' => 'Cannot delete a borrowed book.']);
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', "$book->title has been removed.");
>>>>>>> Stashed changes
    }
}