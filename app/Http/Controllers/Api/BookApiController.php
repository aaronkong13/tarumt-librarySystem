<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookService;
use App\Services\BookSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * BookApiController - REST API for Book Module
 * 
 * Provides JSON endpoints for CRUD operations on books.
 * Can be consumed by mobile apps, frontend frameworks, or external services.
 */
class BookApiController extends Controller
{
    private BookService $bookService;
    private BookSecurityService $securityService;

    public function __construct(BookService $bookService, BookSecurityService $securityService)
    {
        $this->bookService = $bookService;
        $this->securityService = $securityService;
    }

    /**
     * GET /api/books
     * Get all books with optional filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $books = $this->bookService->getFilteredBooks($request, 15);

            return response()->json([
                'success' => true,
                'message' => 'Books retrieved successfully',
                'data' => $books->items(),
                'pagination' => [
                    'total' => $books->total(),
                    'per_page' => $books->perPage(),
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving books',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/books/{id}
     * Get a single book by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $book = $this->bookService->getBookById($id);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Book retrieved successfully',
                'data' => $book,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving book',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/books
     * Create a new book
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'author' => ['required', 'string', 'max:255'],
                'isbn' => ['required', 'string', 'max:20', 'unique:books'],
                'year' => ['required', 'integer', 'min:0', 'max:3000'],
                'category' => ['required', 'string', 'max:100'],
                'status' => ['required', 'in:Available,Borrowed,Lost,Damaged'],
                'cover_image' => ['nullable', 'file', 'mimes:jpeg,png,gif', 'max:2048'],
            ]);

            // Sanitize data
            $cleanData = $this->securityService->validateAndSanitizeBookData($validated);

            // Handle file upload
            if ($request->hasFile('cover_image')) {
                $this->securityService->validateCoverImage($request->file('cover_image'));
                $cleanData['cover_image'] = file_get_contents($request->file('cover_image')->getRealPath());
            }

            // Create book
            $book = $this->bookService->createBook($cleanData);

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => $book,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating book',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/books/{id}
     * Update an existing book
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $book = $this->bookService->getBookById($id);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found',
                ], 404);
            }

            // Validate input
            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'author' => ['sometimes', 'string', 'max:255'],
                'isbn' => ['sometimes', 'string', 'max:20', 'unique:books,isbn,' . $id . ',bookId'],
                'year' => ['sometimes', 'integer', 'min:0', 'max:3000'],
                'category' => ['sometimes', 'string', 'max:100'],
                'status' => ['sometimes', 'in:Available,Borrowed,Lost,Damaged'],
                'cover_image' => ['nullable', 'file', 'mimes:jpeg,png,gif', 'max:2048'],
            ]);

            // Sanitize data
            $cleanData = $this->securityService->validateAndSanitizeBookData($validated);

            // Handle file upload
            if ($request->hasFile('cover_image')) {
                $this->securityService->validateCoverImage($request->file('cover_image'));
                $cleanData['cover_image'] = file_get_contents($request->file('cover_image')->getRealPath());
            }

            // Update book
            $updated = $this->bookService->updateBook($id, $cleanData);

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => $updated,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating book',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/books/{id}
     * Delete a book (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $book = $this->bookService->getBookById($id);

            if (!$book) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found',
                ], 404);
            }

            // Prevent deletion of borrowed books
            if ($book->status === 'Borrowed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a borrowed book',
                ], 400);
            }

            $this->bookService->deleteBook($id);

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting book',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/books/status/{status}
     * Get books by status
     */
    public function getByStatus($status): JsonResponse
    {
        try {
            $books = $this->bookService->getBooksByStatus($status);

            return response()->json([
                'success' => true,
                'message' => "Books with status '$status' retrieved successfully",
                'data' => $books,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving books',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/books/category/{category}
     * Get books by category
     */
    public function getByCategory($category): JsonResponse
    {
        try {
            $books = $this->bookService->getBooksByCategory($category);

            return response()->json([
                'success' => true,
                'message' => "Books in category '$category' retrieved successfully",
                'data' => $books,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving books',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/books/stats
     * Get book statistics
     */
    public function stats(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Book statistics retrieved successfully',
                'data' => [
                    'total_books' => $this->bookService->getTotalBooks(),
                    'available' => $this->bookService->getAvailableCount(),
                    'borrowed' => $this->bookService->getBorrowedCount(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
