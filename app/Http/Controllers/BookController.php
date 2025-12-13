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

    private function currentUserOrStub()
    {
        // Temporarily allow testing without auth; falls back to a stub staff user
        return auth()->user() ?: (object) ['id' => 0, 'role' => 'staff'];
    }

    // --- LIST ---
    public function index(Request $request, BookSecurityService $security)
    {
        $security->enforceStaffAccess($this->currentUserOrStub());

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
        $security->enforceStaffAccess($this->currentUserOrStub());

        return view('books.create');
    }

    // --- STORE ---
    public function store(Request $request, BookSecurityService $security)
    {
        $security->enforceStaffAccess($this->currentUserOrStub());

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
        $security->enforceStaffAccess($this->currentUserOrStub());

        return view('books.edit', compact('book'));
    }

    // --- UPDATE ---
    public function update(Request $request, Book $book, BookSecurityService $security)
    {
        $security->enforceStaffAccess($this->currentUserOrStub());

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
        $security->enforceStaffAccess($this->currentUserOrStub());

        if ($book->status === 'Borrowed') {
            return back()->withErrors(['message' => 'Cannot delete a borrowed book.']);
        }

        $book->delete();

        return redirect()->route('books.index')->with('success', "$book->title has been removed.");
    }
}