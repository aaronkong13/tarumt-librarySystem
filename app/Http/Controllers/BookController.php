<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\BookSecurityService;
use App\Services\BookService;
use App\Services\BookSearch\BookSearchContext;
use App\Services\BookSearch\Strategies\CategoryFilter;
use App\Services\BookSearch\Strategies\KeywordFilter;
use App\Services\BookSearch\Strategies\SortStrategy;
use App\Services\BookSearch\Strategies\StatusFilter;
use App\Services\BookSearch\Strategies\YearRangeFilter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * BookController - Handles book management web UI
 * 
 * Responsibilities:
 * - Display book catalogs for students and staff
 * - Manage book creation/editing (Staff/Admin only)
 * - Apply search filters and sorting
 * - Enforce authorization rules
 * 
 * Note: For API access, see Api\BookApiController instead
 */
class BookController extends Controller
{
    private BookService $bookService;
    private BookSearchContext $searchContext;

    public function __construct()
    {
        // Set up search strategies using Strategy pattern
        // This allows filters to be composed and reused
        $this->searchContext = new BookSearchContext([
            new KeywordFilter(),      // Search by title/author/ISBN
            new StatusFilter(),       // Filter by availability status
            new CategoryFilter(),     // Filter by subject category
            new YearRangeFilter(),    // Filter by publication year range
            new SortStrategy(),       // Sort by various fields
        ]);

        $this->bookService = new BookService($this->searchContext);
    }

    /**
     * Get list of all available book categories in the system
     * Centralized so it's consistent across all forms and views
     * 
     * @return array
     */
    private function getCategories(): array
    {
        return [
            'Computer Science',
            'Information Technology',
            'Engineering',
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'Medicine',
            'Business Administration',
            'Economics',
            'Accounting',
            'Marketing',
            'Management',
            'Law',
            'Psychology',
            'Sociology',
            'Literature',
            'History',
            'Philosophy',
            'Education',
            'Language & Linguistics',
            'Art & Design',
            'Architecture',
            'Communication',
            'Research Methods',
            'Reference Materials',
            'General Knowledge'
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // LIST / VIEW OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Display the staff/admin book management dashboard
     * Shows all books with filtering and allows CRUD operations
     * 
     * Staff only endpoint - includes borrowing statistics via AJAX
     */
    public function index(Request $request, BookSecurityService $security)
    {
        // Verify user is staff or admin
        $security->enforceStaffAccess(Auth::user());

        // Get filtered books from database
        $books = $this->bookService->getFilteredBooks($request, 10);

        // Add placeholder for borrowing stats (will be loaded via AJAX when user clicks circulation button)
        // This avoids making multiple API calls on initial page load which causes lag
        $books->getCollection()->transform(function ($book) {
            $book->borrowing_stats = [
                'total_borrows' => '...',  // Placeholder - loaded via AJAX modal
                'completed_borrows' => 0,
                'currently_borrowed' => 0,
                'unique_borrowers' => 0,
            ];
            return $book;
        });

        // If this is an AJAX request (filter/sort from JavaScript)
        if ($request->ajax()) {
            return view('layouts.book-table', ['books' => $books])->render();
        }

        // Normal request - return full page
        return view('books.index', [
            'books' => $books,
            'filters' => $request->only(['q', 'status', 'category', 'year_from', 'year_to', 'sort']),
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Display the public student book catalog
     * Students can search and browse, but cannot edit
     * Shows availability and borrow actions
     * 
     * Public endpoint - no role restriction
     */
    public function catalog(Request $request)
    {
        // Get filtered books for display
        $books = $this->bookService->getFilteredBooks($request, 12);

        // If this is an AJAX request (filter/sort from JavaScript)
        if ($request->ajax()) {
            return view('layouts.book-cards', ['books' => $books])->render();
        }

        // Normal request - return full page
        return view('books.student-book', [
            'books' => $books,
            'filters' => $request->only(['q', 'status', 'category', 'year_from', 'year_to', 'sort']),
            'categories' => $this->getCategories(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // CREATE OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Show form to create a new book
     * Staff/Admin only
     */
    public function create(BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        return view('books.create', [
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Save a new book to the database
     * Validates all input, sanitizes data, and handles file upload
     * Staff/Admin only
     */
    public function store(Request $request, BookSecurityService $security)
    {
        // Verify user is staff or admin
        $security->enforceStaffAccess(Auth::user());

        // Validate all input fields
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'author'   => ['required', 'string', 'max:255'],
            'isbn'     => ['required', 'string', 'max:20', 'unique:books,isbn,NULL,bookId'],  // Must be unique
            'year'     => ['required', 'integer', 'min:1500', 'max:' . (int)date('Y')],
            'category' => ['required', 'string', 'max:100'],
            'status'   => ['required', 'in:Available,Borrowed,Lost,Damaged'],
            'cover'    => ['nullable', 'file', 'mimes:jpeg,png,gif', 'max:2048'],
        ]);

        // Sanitize data for security (prevents injection attacks)
        $cleanData = $security->validateAndSanitizeBookData($validated);

        // Handle book cover image upload if provided
        if ($request->hasFile('cover')) {
            $security->validateCoverImage($request->file('cover'));
            // Read file and store as binary BLOB in database
            $cleanData['cover_image'] = file_get_contents($request->file('cover')->getRealPath());
        }

        // Create book in database via service
        $this->bookService->createBook($cleanData);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book created successfully!');
    }

    // ═══════════════════════════════════════════════════════════════
    // EDIT OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Show form to edit an existing book
     * Staff/Admin only
     */
    public function edit(Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        return view('books.edit', [
            'book' => $book,
            'categories' => $this->getCategories(),
        ]);
    }

    /**
     * Update an existing book with new information
     * Staff/Admin only
     */
    public function update(Request $request, Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        // Validate updated fields
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'author'   => ['required', 'string', 'max:255'],
            'isbn'     => ['required', 'string', 'max:20', 'unique:books,isbn,' . $book->bookId . ',bookId'],  // Unique except this book
            'year'     => ['required', 'integer', 'min:1500', 'max:' . (int)date('Y')],
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

        // Use BookService to update book in database
        $this->bookService->updateBook($book->bookId, $cleanData);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book updated successfully!');
    }

    // ═══════════════════════════════════════════════════════════════
    // DELETE OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Soft delete a book from the system
     * Book is hidden but can be restored if needed
     * Staff/Admin only
     */
    public function destroy(Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        // Prevent deletion of currently borrowed books
        if ($book->status === 'Borrowed') {
            return back()->withErrors([
                'message' => 'Cannot delete a book that is currently borrowed. Return it first.'
            ]);
        }
        
        $bookTitle = $book->title;
        
        // Use BookService to delete book from database (soft delete)
        $this->bookService->deleteBook($book->bookId);

        return redirect()
            ->route('books.index')
            ->with('success', "'{$bookTitle}' has been removed from the system.");
    }
}