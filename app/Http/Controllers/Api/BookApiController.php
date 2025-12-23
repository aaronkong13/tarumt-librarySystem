<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookService;
use App\Services\BookSecurityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

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
     * Clean binary data from book model for JSON serialization
     * Removes cover_image BLOB to prevent UTF-8 encoding errors
     */
    private function cleanBookData($book)
    {
        if ($book instanceof \Illuminate\Database\Eloquent\Collection) {
            return $book->map(function($item) {
                return $this->cleanBookData($item);
            });
        }

        if ($book instanceof \Illuminate\Pagination\Paginator) {
            $items = $book->items();
            $cleaned = array_map(function($item) {
                $item->cover_image = null; // Remove binary data
                return $item;
            }, $items);
            return $cleaned;
        }

        // Single book model
        if (is_object($book) && method_exists($book, 'toArray')) {
            $book->cover_image = null; // Remove binary data
        }

        return $book;
    }
    /**
     * GET /api/books
     * Get all books with optional filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $books = $this->bookService->getFilteredBooks($request, 13);

            // Convert BLOB cover_image to base64 for JSON serialization
            $cleanedBooks = array_map(function($book) {
                if ($book->cover_image) {
                    $book->cover_image = 'data:image/jpeg;base64,' . base64_encode($book->cover_image);
                } else {
                    $book->cover_image = null;
                }
                return $book;
            }, $books->items());

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Books retrieved successfully',
                'data' => $cleanedBooks,
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
     * GET /api/books (Internal cross-module endpoint)
     * Get all books with filters for other modules
     */
    // public function getBooks(Request $request): JsonResponse
    // {
    //     try {
    //         $perPage = $request->input('per_page', 12);
    //         $books = $this->bookService->getFilteredBooks($request, $perPage);

    //         // Convert BLOB cover_image to base64 for JSON serialization
    //         $cleanedBooks = array_map(function($book) {
    //             if ($book->cover_image) {
    //                 $book->cover_image = 'data:image/jpeg;base64,' . base64_encode($book->cover_image);
    //             } else {
    //                 $book->cover_image = null;
    //             }
    //             return $book;
    //         }, $books->items());

    //         // Clear output buffers to prevent BOM issues
    //         while (ob_get_level()) {
    //             ob_end_clean();
    //         }
    //         ob_start();

    //         return response()->json([
    //             'success' => true,
    //             'timestamp' => now()->toIso8601String(),
    //             'message' => 'Books retrieved successfully',
    //             'data' => $cleanedBooks,
    //             'pagination' => [
    //                 'total' => $books->total(),
    //                 'per_page' => $books->perPage(),
    //                 'current_page' => $books->currentPage(),
    //                 'last_page' => $books->lastPage(),
    //                 'from' => $books->firstItem(),
    //                 'to' => $books->lastItem(),
    //             ],
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error retrieving books',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

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
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Book not found',
                ], 404);
            }

            // Convert BLOB cover_image to base64
            if ($book->cover_image) {
                $book->cover_image = 'data:image/jpeg;base64,' . base64_encode($book->cover_image);
            } else {
                $book->cover_image = null;
            }

            // Clear ALL output buffers to remove any BOM or whitespace
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book retrieved successfully',
                'data' => $book,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                // Unique ISBN across books
                'isbn' => ['required', 'string', 'max:20', 'unique:books,isbn,NULL,bookId'],
                // Realistic publication year range
                'year' => ['required', 'integer', 'min:1500', 'max:' . (int)date('Y')],
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book created successfully',
                'data' => $book,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Book not found',
                ], 404);
            }

            // Validate input
            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'author' => ['sometimes', 'string', 'max:255'],
                'isbn' => ['sometimes', 'string', 'max:20', 'unique:books,isbn,' . $id . ',bookId'],
                'year' => ['sometimes', 'integer', 'min:1500', 'max:' . (int)date('Y')],
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book updated successfully',
                'data' => $updated,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Book not found',
                ], 404);
            }

            // Prevent deletion of borrowed books
            if ($book->status === 'Borrowed') {
                return response()->json([
                    'success' => false,
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Cannot delete a borrowed book',
                ], 400);
            }

            $this->bookService->deleteBook($id);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => "Books with status '$status' retrieved successfully",
                'data' => $books,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving books',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/books/available
     * Get all available books (for borrowing module)
     */
    public function getAvailableBooks(): JsonResponse
    {
        try {
            $books = $this->bookService->getBooksByStatus('Available');

            // Clean and format books - test each field
            $formattedBooks = [];

            foreach ($books as $book) {
                $item = [];

                // Test each field individually
                try {
                    // Convert binary image to base64 for JSON transmission
                    $coverImage = '';
                    if (!empty($book->cover_image)) {
                        // If it's binary data, convert to base64
                        if (!mb_check_encoding($book->cover_image, 'UTF-8')) {
                            $coverImage = 'data:image/jpeg;base64,' . base64_encode($book->cover_image);
                        } else {
                            // If it's already a string path, use as is
                            $coverImage = $book->cover_image;
                        }
                    }

                    $item['bookId'] = $book->bookId;
                    $item['title'] = $book->title ?? '';
                    $item['author'] = $book->author ?? '';
                    $item['isbn'] = $book->isbn ?? '';
                    $item['category'] = $book->category ?? '';
                    $item['year'] = $book->year ?? '';
                    $item['status'] = $book->status ?? 'Available';
                    $item['cover_image'] = $coverImage;

                    $formattedBooks[] = $item;
                } catch (\Exception $e) {
                    \Log::error('Error processing book ID ' . $book->bookId . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Clear ALL output buffers to remove any BOM or whitespace
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Available books retrieved successfully',
                'data' => $formattedBooks,
            ], 200, ['Content-Type' => 'application/json; charset=UTF-8']);
        } catch (\Exception $e) {
            \Log::error('Error in getAvailableBooks: ' . $e->getMessage());
            \Log::error('Line: ' . $e->getLine());

            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving available books',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean UTF-8 encoding for text fields
     */
    private function cleanUtf8($text)
    {
        if (empty($text)) {
            return '';
        }

        // Remove any non-UTF-8 characters
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Additional cleaning: remove control characters except newlines/tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        return $text;
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
                'timestamp' => now()->toIso8601String(),
                'message' => "Books in category '$category' retrieved successfully",
                'data' => $books,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/books/{id}/borrowing-history
     * Get borrowing history for a specific book
     *
     * Cross-Module API Call: Book Module → Borrowing Module
     */
    public function borrowingHistory(int $id): JsonResponse
    {
        try {
            // Verify book exists (same module, direct access OK)
            $book = $this->bookService->getBookById($id);
            if (!$book) {
                return response()->json([
                    'success' => false,
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Book not found',
                ], 404);
            }

            // Call BorrowingApiController via HTTP (cross-module: Book → Borrowing)
            $apiUrl = config('app.api_url', config('app.url'));
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => request()->header('Authorization'),
            ])->get("{$apiUrl}/api/borrowings/books/{$id}/stats");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Failed to retrieve borrowing statistics',
                    'error' => $response->json()['message'] ?? 'Unknown error',
                ], $response->status());
            }

            $borrowingData = $response->json()['data'];

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book borrowing history retrieved successfully',
                'book' => [
                    'id' => $book->bookId,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                ],
                'borrowing_stats' => $borrowingData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving borrowing history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
