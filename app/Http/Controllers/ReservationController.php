<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * ReservationController - Manages Book Reservations
 * 
 * Separation of Responsibilities:
 * - Students: Can reserve, cancel own reservations, view own queue position
 * - Staff: Can view all reservation queues
 */
class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Reserve a book (Student Only)
     * POST /reservations
     * 
     * Rules:
     * - Book must be borrowed (not available)
     * - No duplicate reservations
     * - Max 5 active reservations per student
     */
    public function store(Request $request)
    {
        \Log::info('=== Reservation Request Started ===', [
            'all_input' => $request->all(),
            'user_id' => Auth::id(),
        ]);

        try {
            // Input validation (whitelist)
            $validated = $request->validate([
                'book_id' => 'required|integer|exists:books,bookId',
            ]);

            $userId = Auth::id();
            $bookId = $validated['book_id'];

            \Log::info('Validation passed', [
                'user_id' => $userId,
                'book_id' => $bookId,
            ]);

            // Business logic handled in service layer
            $reservation = $this->reservationService->reserveBook($userId, $bookId);

            \Log::info('Reservation created successfully', [
                'reservation_id' => $reservation->id,
                'queue_position' => $reservation->queue_position,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book reserved successfully. You are position #' . $reservation->queue_position . ' in the queue.',
                    'data' => $reservation,
                ], 201);
            }

            return redirect()->back()->with('success', "Book reserved successfully! You are position #{$reservation->queue_position} in the queue.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Reservation validation failed', [
                'errors' => $e->errors(),
            ]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (Exception $e) {
            \Log::error('Reservation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel reservation (Student Only - Own reservations)
     * DELETE /reservations/{id}
     * 
     * Authorization: Only reservation owner can cancel
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $userId = Auth::id();

            // Service layer enforces authorization
            $this->reservationService->cancelReservation($id, $userId);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reservation cancelled successfully.',
                ]);
            }

            return redirect()->back()->with('success', 'Reservation cancelled successfully.');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * View my reservations (Student)
     * GET /my-reservations
     * 
     * Shows:
     * - Book details
     * - Queue position
     * - Status (Waiting / Notified)
     * - Expiry countdown
     */
    public function myReservations()
    {
        $userId = Auth::id();
        $reservations = $this->reservationService->getUserReservations($userId);

        return view('reservations.my-reservations', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * View my notifications (Student)
     * GET /my-notifications
     * 
     * Shows books that are now available (notified status)
     */
    public function myNotifications()
    {
        $userId = Auth::id();
        $notifications = $this->reservationService->getUserNotifications($userId);

        return view('reservations.my-notifications', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * View reservation queue for a book (Staff Only)
     * GET /reservations/queue/{bookId}
     * 
     * Shows:
     * - All students in queue
     * - Queue positions
     * - Notification status
     * - Expiry deadlines
     */
    public function viewQueue(int $bookId)
    {
        // Check staff permission
        if (!Auth::user()->isStaff() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $book = Book::findOrFail($bookId);
        $queue = $this->reservationService->getReservationQueue($bookId);

        return view('reservations.queue', [
            'book' => $book,
            'queue' => $queue,
        ]);
    }

    /**
     * View all reservation queues (Staff Only)
     * GET /reservations/all-queues
     * 
     * Shows books with active reservations
     */
    public function allQueues()
    {
        // Check staff permission
        if (!Auth::user()->isStaff() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Get all books with active reservations
        $books = Book::whereHas('reservations', function ($query) {
            $query->whereIn('status', ['waiting', 'notified', 'active']);
        })
        ->withCount(['reservations' => function ($query) {
            $query->whereIn('status', ['waiting', 'notified', 'active']);
        }])
        ->get();

        return view('reservations.all-queues', [
            'books' => $books,
        ]);
    }

    /**
     * Check book reservation status (AJAX)
     * GET /reservations/check/{bookId}
     * 
     * Returns whether user can reserve this book
     */
    public function checkReservationStatus(int $bookId)
    {
        $userId = Auth::id();
        $book = Book::findOrFail($bookId);

        // Check if user already has reservation
        $hasReservation = \App\Models\Reservation::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->whereIn('status', ['waiting', 'notified', 'active'])
            ->exists();

        // Get queue count
        $queueCount = \App\Models\Reservation::where('book_id', $bookId)
            ->whereIn('status', ['waiting', 'notified', 'active'])
            ->count();

        return response()->json([
            'can_reserve' => $book->status === 'Borrowed' && !$hasReservation,
            'has_reservation' => $hasReservation,
            'queue_count' => $queueCount,
            'book_status' => $book->status,
        ]);
    }

    /**
     * Get reservation statistics (API)
     * GET /api/reservations/stats/overview
     * 
     * Returns overview statistics for reservations
     */
    public function stats()
    {
        $totalReservations = \App\Models\Reservation::count();
        $activeReservations = \App\Models\Reservation::whereIn('status', ['waiting', 'notified', 'active'])->count();
        $fulfilledReservations = \App\Models\Reservation::where('status', 'fulfilled')->count();
        $expiredReservations = \App\Models\Reservation::where('status', 'expired')->count();
        $cancelledReservations = \App\Models\Reservation::where('status', 'cancelled')->count();
        
        // Average wait time (days between reservation_date and notified_at)
        $avgWaitTime = \App\Models\Reservation::whereNotNull('notified_at')
            ->whereNotNull('reservation_date')
            ->selectRaw('AVG(DATEDIFF(notified_at, reservation_date)) as avg_days')
            ->value('avg_days');

        return response()->json([
            'total' => $totalReservations,
            'active' => $activeReservations,
            'fulfilled' => $fulfilledReservations,
            'expired' => $expiredReservations,
            'cancelled' => $cancelledReservations,
            'average_wait_days' => round($avgWaitTime ?? 0, 1),
        ]);
    }

    /**
     * Display the public student book catalog
     * Students can browse and reserve books
     * Fetches books via API call (cross-module: Reservation → Book)
     * 
     * GET /books/catalog
     */
    public function catalog(Request $request)
    {
        \Log::info('Catalog request', [
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'x_requested_with' => $request->header('X-Requested-With'),
            'all_headers' => $request->headers->all(),
        ]);

        $apiUrl = config('app.api_url', config('app.url'));
        $books = [];
        $pagination = [];
        
        try {
            // Build query parameters
            $params = array_filter([
                'q' => $request->input('q'),
                'status' => $request->input('status'),
                'category' => $request->input('category'),
                'year_from' => $request->input('year_from'),
                'year_to' => $request->input('year_to'),
                'sort' => $request->input('sort'),
                'page' => $request->input('page', 1),
                'per_page' => 12,
            ]);
            
            $queryString = http_build_query($params);
            $response = \Illuminate\Support\Facades\Http::get("{$apiUrl}/api/books?{$queryString}");
            
            if ($response->successful()) {
                $body = $response->body();
                // Remove BOM if present
                if (substr($body, 0, 3) === "\xEF\xBB\xBF") {
                    $body = substr($body, 3);
                }
                $jsonData = json_decode($body, true);
                
                if ($jsonData && isset($jsonData['success']) && $jsonData['success']) {
                    $booksData = $jsonData['data'] ?? [];
                    $pagination = $jsonData['pagination'] ?? [];
                    
                    // Convert array data to collection for compatibility with blade template
                    $books = collect($booksData)->map(function($bookData) {
                        return (object) $bookData;
                    });
                    
                    // Create a paginator instance
                    $books = new \Illuminate\Pagination\LengthAwarePaginator(
                        $books,
                        $pagination['total'] ?? 0,
                        $pagination['per_page'] ?? 12,
                        $pagination['current_page'] ?? 1,
                        ['path' => $request->url(), 'query' => $request->query()]
                    );
                } else {
                    \Log::error('API returned unsuccessful response', [
                        'status' => $response->status(),
                        'json' => $jsonData
                    ]);
                    $books = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12, 1);
                }
            } else {
                \Log::error('API call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                $books = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12, 1);
            }
        } catch (Exception $e) {
            // Handle API error gracefully
            \Log::error('Exception calling books API: ' . $e->getMessage());
            $books = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12, 1);
        }

        // Get user's borrowed and reserved books for status display
        $userBorrowedBookIds = [];
        $userReservedBookIds = [];
        
        if (Auth::check()) {
            // Get all book IDs that current user has borrowed
            $userBorrowedBookIds = \App\Models\Borrowing::where('user_id', Auth::id())
                ->where('status', 'borrowed')
                ->pluck('book_id')
                ->toArray();
            
            // Get all book IDs that current user has reserved
            $userReservedBookIds = \App\Models\Reservation::where('user_id', Auth::id())
                ->whereIn('status', ['waiting', 'notified'])
                ->pluck('book_id')
                ->toArray();
        }

        // If this is an AJAX request (filter/sort from JavaScript)
        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('layouts.book-cards', [
                'books' => $books,
                'userBorrowedBookIds' => $userBorrowedBookIds,
                'userReservedBookIds' => $userReservedBookIds,
            ])->render();
        }

        // Get categories for filter dropdown
        $categories = [
            'Fiction',
            'Non-Fiction',
            'Science',
            'Technology',
            'History',
            'Biography',
            'Self-Help',
            'Business',
            'Education',
            'Reference'
        ];

        // Normal request - return full page
        return view('books.student-book', [
            'books' => $books,
            'filters' => $request->only(['q', 'status', 'category', 'year_from', 'year_to', 'sort']),
            'categories' => $categories,
            'userBorrowedBookIds' => $userBorrowedBookIds,
            'userReservedBookIds' => $userReservedBookIds,
        ]);
    }
}
