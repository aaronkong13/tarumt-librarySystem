<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

class BookController extends Controller
{
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

        $query = Book::query();
        $query = $this->searchContext->apply($query, $request); //here I apply strategy pattern for search/filter/sort , you can ref this

        $books = $query
            ->paginate(10)
            ->appends($request->query());

        // AJAX request - return partial view
        if ($request->ajax()) {
            return view('books.partials.book-table', [
                'books' => $books
            ])->render();
        }

        // Normal request - return full page
        return view('books.index', [
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
            'isbn' => ['required', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:0', 'max:3000'],
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

        Book::create($cleanData);

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
    }
}