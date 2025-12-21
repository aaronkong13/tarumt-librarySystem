<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReservationService;
use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * ReservationApiController - REST API for Reservation Module
 *
 * Architecture matches BookApiController and BorrowingApiController:
 * - Uses dependency injection for ReservationService
 * - Provides JSON endpoints for reservation operations
 * - Delegates all business logic to ReservationService
 * - Can be consumed by mobile apps, frontend frameworks, or external services
 */
class ReservationApiController extends Controller
{
    private ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        // Dependency injection - same pattern as other API controllers
        $this->reservationService = $reservationService;
    }

    /**
     * POST /api/reservations
     * Create a new reservation (Student only)
     *
     * Business Rules:
     * - Book must be borrowed (not available)
     * - No duplicate reservations
     * - Max 5 active reservations per student
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Input validation (whitelist)
            $validated = $request->validate([
                'book_id' => 'required|integer|exists:books,bookId',
            ]);

            $userId = Auth::id();
            $bookId = $validated['book_id'];

            // Delegate to service layer
            $reservation = $this->reservationService->reserveBook($userId, $bookId);

            return response()->json([
                'success' => true,
                'message' => 'Book reserved successfully. You are position #' . $reservation->queue_position . ' in the queue.',
                'data' => $reservation,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * DELETE /api/reservations/{id}
     * Cancel a reservation (Student only - own reservations)
     *
     * Authorization: Only reservation owner can cancel
     */
    public function destroy($id): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Service layer enforces authorization
            $this->reservationService->cancelReservation($id, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Reservation cancelled successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/reservations/my-reservations
     * Get current user's active reservations
     *
     * Returns:
     * - Book details
     * - Queue position
     * - Status (Waiting / Notified)
     * - Expiry countdown
     */
    public function myReservations(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $reservations = $this->reservationService->getUserReservations($userId);

            return response()->json([
                'success' => true,
                'message' => 'Reservations retrieved successfully',
                'data' => $reservations,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving reservations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/my-notifications
     * Get current user's notifications
     *
     * Returns books that are now available (notified status)
     */
    public function myNotifications(): JsonResponse
    {
        try {
            $userId = Auth::id();
            $notifications = $this->reservationService->getUserNotifications($userId);

            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => $notifications,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/queue/{bookId}
     * View reservation queue for a specific book
     *
     * Students: Can see own position
     * Staff: Can see full queue
     */
    public function viewQueue($bookId): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::findOrFail($bookId);
            $queue = $this->reservationService->getReservationQueue($bookId);

            // If student, filter to show only their own position
            if (!$user->isStaff() && !$user->isAdmin()) {
                $queue = collect($queue)->filter(function ($reservation) use ($user) {
                    return $reservation['user_id'] === $user->id;
                })->values()->all();
            }

            return response()->json([
                'success' => true,
                'message' => 'Queue retrieved successfully',
                'data' => [
                    'book' => $book,
                    'queue' => $queue,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving queue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/queues/all
     * View all reservation queues (Staff only)
     *
     * Returns all books with active reservations
     */
    public function allQueues(): JsonResponse
    {
        try {
            // Check staff permission
            if (!Auth::user()->isStaff() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            // Get all books with active reservations
            $books = Book::whereHas('reservations', function ($query) {
                $query->whereIn('status', ['waiting', 'notified', 'active']);
            })
            ->withCount(['reservations' => function ($query) {
                $query->whereIn('status', ['waiting', 'notified', 'active']);
            }])
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'All queues retrieved successfully',
                'data' => $books,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving queues',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/check/{bookId}
     * Check if user can reserve a book
     *
     * Returns reservation eligibility and queue status
     */
    public function checkStatus($bookId): JsonResponse
    {
        try {
            $userId = Auth::id();
            $book = Book::findOrFail($bookId);

            // Check if user already has reservation
            $hasReservation = Reservation::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->whereIn('status', ['waiting', 'notified', 'active'])
                ->exists();

            // Get queue count
            $queueCount = Reservation::where('book_id', $bookId)
                ->whereIn('status', ['waiting', 'notified', 'active'])
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'can_reserve' => $book->status === 'Borrowed' && !$hasReservation,
                    'has_reservation' => $hasReservation,
                    'queue_count' => $queueCount,
                    'book_status' => $book->status,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking reservation status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/stats/overview
     * Get reservation statistics
     *
     * Returns overview statistics for all reservations
     */
    public function stats(): JsonResponse
    {
        try {
            $totalReservations = Reservation::count();
            $activeReservations = Reservation::whereIn('status', ['waiting', 'notified', 'active'])->count();
            $fulfilledReservations = Reservation::where('status', 'fulfilled')->count();
            $expiredReservations = Reservation::where('status', 'expired')->count();
            $cancelledReservations = Reservation::where('status', 'cancelled')->count();

            // Average wait time (days between reservation_date and notified_at)
            $avgWaitTime = Reservation::whereNotNull('notified_at')
                ->whereNotNull('reservation_date')
                ->selectRaw('AVG(DATEDIFF(notified_at, reservation_date)) as avg_days')
                ->value('avg_days');

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => [
                    'total' => $totalReservations,
                    'active' => $activeReservations,
                    'fulfilled' => $fulfilledReservations,
                    'expired' => $expiredReservations,
                    'cancelled' => $cancelledReservations,
                    'average_wait_days' => round($avgWaitTime ?? 0, 1),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/ready-for-pickup
     * Get reservations that are ready for pickup (Staff only)
     *
     * Returns reservations where book is available/reserved and not currently borrowed
     * Groups by book to avoid duplicates - only shows first in queue per book
     */
    public function readyForPickup(): JsonResponse
    {
        try {
            // Check staff permission
            if (!Auth::user()->isStaff() && !Auth::user()->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            // Get reservations where book is ready for pickup
            // Only get first in queue per book (queue_position = 1 or lowest)
            $reservations = Reservation::whereIn('status', ['waiting', 'notified', 'active'])
                ->with(['book', 'user'])
                ->whereHas('book', function($q) {
                    $q->whereIn('status', ['Available', 'Reserved'])
                      ->whereDoesntHave('borrowings', fn($bq) => $bq->where('status', 'borrowed'));
                })
                ->orderBy('book_id')
                ->orderBy('queue_position')
                ->get()
                ->groupBy('book_id')
                ->map(function($group) {
                    // Return only the first reservation per book (first in queue)
                    return $group->first();
                })
                ->values();

            $data = $reservations->map(function($reservation) {
                return [
                    'reservation_id' => $reservation->id,
                    'book_id' => $reservation->book_id,
                    'book_title' => $reservation->book->title,
                    'book_author' => $reservation->book->author,
                    'book_cover' => $reservation->book->cover_image ? base64_encode($reservation->book->cover_image) : null,
                    'book_status' => $reservation->book->status,
                    'user_id' => $reservation->user_id,
                    'user_name' => $reservation->user->name,
                    'user_email' => $reservation->user->email,
                    'reservation_status' => $reservation->status,
                    'queue_position' => $reservation->queue_position,
                    'expiry_date' => $reservation->expiry_date?->format('M d, Y'),
                    'notified_at' => $reservation->notified_at?->format('M d, Y H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Ready for pickup reservations retrieved successfully',
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ready for pickup reservations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reservations/users/{userId}/history
     * Get reservation history for a specific user (for user management)
     */
    public function getUserReservations($userId): JsonResponse
    {
        try {
            $reservations = $this->reservationService->getUserReservationHistory($userId);

            // Process reservations to make them JSON serializable
            $processedReservations = $reservations->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'user_id' => $reservation->user_id,
                    'book_id' => $reservation->book_id,
                    'book_title' => $reservation->book ? $reservation->book->title : 'Book not found',
                    'book_author' => $reservation->book ? $reservation->book->author : 'Unknown',
                    'book_isbn' => $reservation->book ? $reservation->book->isbn : null,
                    'book_cover_image' => $reservation->book ? ($reservation->book->cover_image ? base64_encode($reservation->book->cover_image) : null) : null,
                    'reservation_date' => $reservation->reservation_date,
                    'expiry_date' => $reservation->expiry_date,
                    'status' => $reservation->status,
                    'queue_position' => $reservation->queue_position,
                    'notified_at' => $reservation->notified_at,
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'User reservations retrieved successfully',
                'data' => $processedReservations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user reservations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
