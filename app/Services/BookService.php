<?php

namespace App\Services;

use App\Models\Book;
use App\Services\BookSearch\BookSearchContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

/**
 * BookService - Data Access Layer for Book Module
 * 
 * This service layer handles all database operations for the Book model.
 * It provides a clean separation between controllers and database logic,
 * following the Repository/Service Pattern for better maintainability.
 */
class BookService
{
    private BookSearchContext $searchContext;

    public function __construct(BookSearchContext $searchContext)
    {
        $this->searchContext = $searchContext;
    }

    /**
     * Get all books without filters
     */
    public function getAllBooks()
    {
        return Book::all();
    }

    /**
     * Get books with filtering, sorting, and pagination
     * 
     * @param Request $request - Contains filter parameters (q, status, category, year_from, year_to, sort)
     * @param int $perPage - Number of items per page
     * @return Paginator
     */
    public function getFilteredBooks(Request $request, int $perPage = 10)
    {
        $query = Book::query();
        $query = $this->searchContext->apply($query, $request);

        return $query->paginate($perPage)->appends($request->query());
    }

    /**
     * Get a single book by ID
     * 
     * @param int $id - Book ID (bookId)
     * @return Book|null
     */
    public function getBookById($id)
    {
        return Book::find($id);
    }

    /**
     * Create a new book
     * 
     * @param array $data - Book data (title, author, isbn, year, category, status, cover_image)
     * @return Book
     */
    public function createBook(array $data)
    {
        return Book::create($data);
    }

    /**
     * Update an existing book
     * 
     * @param int $id - Book ID (bookId)
     * @param array $data - Fields to update
     * @return Book|null
     */
    public function updateBook($id, array $data)
    {
        $book = Book::find($id);
        
        if ($book) {
            $book->update($data);
        }

        return $book;
    }

    /**
     * Soft delete a book (marks as deleted without removing from DB)
     * 
     * @param int $id - Book ID (bookId)
     * @return bool
     */
    public function deleteBook($id)
    {
        $book = Book::find($id);
        
        if ($book) {
            return $book->delete();
        }

        return false;
    }

    /**
     * Get books by status
     * 
     * @param string $status - Status value (Available, Borrowed, Lost, Damaged)
     * @return Collection
     */
    public function getBooksByStatus($status)
    {
        return Book::where('status', $status)->get();
    }

    /**
     * Get books by category
     * 
     * @param string $category - Category name
     * @return Collection
     */
    public function getBooksByCategory($category)
    {
        return Book::where('category', $category)->get();
    }

    /**
     * Get total book count
     * 
     * @return int
     */
    public function getTotalBooks()
    {
        return Book::count();
    }

    /**
     * Get available books count
     * 
     * @return int
     */
    public function getAvailableCount()
    {
        return Book::where('status', 'Available')->count();
    }

    /**
     * Get borrowed books count
     * 
     * @return int
     */
    public function getBorrowedCount()
    {
        return Book::where('status', 'Borrowed')->count();
    }
}
