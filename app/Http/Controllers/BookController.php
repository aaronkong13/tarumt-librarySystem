<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\BookSecurityService;
use App\Services\BookService;
use App\Services\BorrowingService;
use App\Services\BookSearch\BookSearchContext;
use App\Services\BookSearch\Strategies\CategoryFilter;
use App\Services\BookSearch\Strategies\KeywordFilter;
use App\Services\BookSearch\Strategies\SortStrategy;
use App\Services\BookSearch\Strategies\StatusFilter;
use App\Services\BookSearch\Strategies\YearRangeFilter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    private BookService $bookService;
    private BorrowingService $borrowingService;
    private BookSearchContext $searchContext;

    public function __construct()
    {
        // Strategy pattern wiring for search/filter/sort
        $this->searchContext = new BookSearchContext([
            new KeywordFilter(),
            new StatusFilter(),
            new CategoryFilter(),
            new YearRangeFilter(),
            new SortStrategy(),
        ]);

        // Inject services
        $this->bookService = new BookService($this->searchContext);
        $this->borrowingService = new BorrowingService();
    }

    // Centralized category list for the library system
    private function getCategories()
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

    // --- LIST ---
    public function index(Request $request, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        // Use BookService to get filtered books from database
        $books = $this->bookService->getFilteredBooks($request, 10);

        // AJAX request - return partial view (layouts.book-table)
        if ($request->ajax()) {
            // Augment each book with borrowing stats
            $books->getCollection()->transform(function($book) {
                $book->borrowing_stats = $this->borrowingService->getBookBorrowingStats($book->bookId);
                return $book;
            });

            return view('layouts.book-table', [
                'books' => $books
            ])->render();
        }

        // Normal request - return full page
        // Augment each book with borrowing stats
        $books->getCollection()->transform(function($book) {
            $book->borrowing_stats = $this->borrowingService->getBookBorrowingStats($book->bookId);
            return $book;
        });

        return view('books.index', [
            'books' => $books,
            'filters' => $request->only(['q', 'status', 'category', 'year_from', 'year_to', 'sort']),
            'categories' => $this->getCategories(),
        ]);
    }

    // --- USER CATALOG (no staff enforcement) ---
    public function catalog(Request $request)
    {
        // Public/user-facing catalog: search, filter, sort; no staff-only actions
        $books = $this->bookService->getFilteredBooks($request, 12);

        if ($request->ajax()) {
            return view('layouts.book-cards', [ 'books' => $books ])->render();
        }

        return view('books.student-book', [
            'books' => $books,
            'filters' => $request->only(['q', 'status', 'category', 'year_from', 'year_to', 'sort']),
            'categories' => $this->getCategories(),
        ]);
    }

    // --- CREATE ---
    public function create(BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        return view('books.create', [
            'categories' => $this->getCategories(),
        ]);
    }

    // --- STORE ---
    public function store(Request $request, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            // OWASP 11 (input validation) + 21 (output encoding considerations): enforce unique, bounded length
            'isbn' => ['required', 'string', 'max:20', 'unique:books,isbn,NULL,bookId'],
            // Year range tightened to realistic publication years
            'year' => ['required', 'integer', 'min:1500', 'max:' . (int)date('Y')],
            'category' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:Available,Borrowed,Lost,Damaged'],
            'cover' => ['nullable', 'file', 'mimes:jpeg,png,gif', 'max:2048'],
        ]);

        $cleanData = $security->validateAndSanitizeBookData($validated);

        if ($request->hasFile('cover')) {
            $security->validateCoverImage($request->file('cover'));
            // Store image as BLOB (binary data)
            $cleanData['cover_image'] = file_get_contents($request->file('cover')->getRealPath());
        }

        // Use BookService to create book in database
        $this->bookService->createBook($cleanData);

        return redirect()->route('books.index')->with('success', 'Book added securely.');
    }

    // --- EDIT ---
    public function edit(Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        return view('books.edit', [
            'book' => $book,
            'categories' => $this->getCategories(),
        ]);
    }

    // --- UPDATE ---
    public function update(Request $request, Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            // Unique ISBN excluding current record (bookId)
            'isbn' => ['required', 'string', 'max:20', 'unique:books,isbn,' . $book->bookId . ',bookId'],
            'year' => ['required', 'integer', 'min:1500', 'max:' . (int)date('Y')],
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

        return redirect()->route('books.index')->with('success', 'Book updated.');
    }

    // --- DELETE (SOFT) ---
    public function destroy(Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess(Auth::user());

        if ($book->status === 'Borrowed') {
            return back()->withErrors(['message' => 'Cannot delete a borrowed book.']);
        }
        
        $title = $book->title;
        // Use BookService to delete book from database
        $this->bookService->deleteBook($book->bookId);

        return redirect()->route('books.index')->with('success', "$title has been removed.");
    }
}