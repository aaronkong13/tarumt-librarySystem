<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BorrowingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * BorrowingApiController - REST API for Borrowing Module
 *
 * Architecture matches BookApiController:
 * - Uses dependency injection for BorrowingService
 * - Provides JSON endpoints for borrowing operations
 * - Delegates all business logic to BorrowingService
 * - Can be consumed by mobile apps, frontend frameworks, or external services
 */
class BorrowingApiController extends Controller
{
    private BorrowingService $borrowingService;

    public function __construct(BorrowingService $borrowingService)
    {
        // Dependency injection - same pattern as BookApiController
        $this->borrowingService = $borrowingService;
    }

    /**
     * POST /api/borrowings/borrow
     * Borrow a book
     */
    public function borrow(Request $request): JsonResponse
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        try {
            $request->validate([
                'book_id' => 'required|integer|exists:books,bookId',
                'user_id' => $isStaff ? 'required|integer|exists:users,id' : 'nullable',
                'duration_days' => 'nullable|integer|min:1|max:30',
            ]);

            $userId = $isStaff ? $request->user_id : auth()->id();
            $durationDays = $request->duration_days ?? null;

            $borrowing = $this->borrowingService->borrowBook($userId, $request->book_id, $durationDays);

            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully',
                'data' => $borrowing,
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
                'message' => 'Error borrowing book',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/borrowings/{borrowing}/return
     * Return a borrowed book
     */
    public function return(Request $request, $borrowingId): JsonResponse
    {
        try {
            $borrowing = $this->borrowingService->returnBook($borrowingId);

            $message = 'Book returned successfully!';
            if ($borrowing->fine_amount > 0) {
                $message .= ' Fine amount: $' . number_format($borrowing->fine_amount, 2);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $borrowing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error returning book',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/borrowings/{borrowing}/renew
     * Renew a borrowing
     */
    public function renew(Request $request, $borrowingId): JsonResponse
    {
        try {
            $request->validate([
                'additional_days' => 'nullable|integer|min:1|max:14',
            ]);

            $additionalDays = $request->additional_days ?? 7;
            $borrowing = $this->borrowingService->renewBorrowing($borrowingId, $additionalDays);

            return response()->json([
                'success' => true,
                'message' => 'Borrowing renewed successfully',
                'data' => $borrowing,
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
                'message' => 'Error renewing borrowing',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/borrowings/history
     * Get borrowing history (current user or staff can pass user_id)
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : auth()->id();

            $borrowings = $this->borrowingService->getUserBorrowingHistory($userId);

            return response()->json([
                'success' => true,
                'message' => 'Borrowing history retrieved successfully',
                'data' => $borrowings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving borrowing history',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/borrowings/active
     * Get active borrowings for current user
     */
    public function activeBorrowings(Request $request): JsonResponse
    {
        try {
            $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : auth()->id();

            $borrowings = $this->borrowingService->getUserActiveBorrowings($userId);

            return response()->json([
                'success' => true,
                'message' => 'Active borrowings retrieved successfully',
                'data' => $borrowings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving active borrowings',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/borrowings/reserve
     * Reserve a book
     */
    public function reserve(Request $request): JsonResponse
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        try {
            $request->validate([
                'book_id' => 'required|integer|exists:books,bookId',
                'user_id' => $isStaff ? 'required|integer|exists:users,id' : 'nullable',
                'expiry_days' => 'nullable|integer|min:1|max:7',
            ]);

            $userId = $isStaff ? $request->user_id : auth()->id();
            $expiryDays = $request->expiry_days ?? null;

            $reservation = $this->borrowingService->reserveBook($userId, $request->book_id, $expiryDays);

            return response()->json([
                'success' => true,
                'message' => 'Book reserved successfully',
                'data' => $reservation,
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
                'message' => 'Error reserving book',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * DELETE /api/borrowings/reservations/{reservation}
     * Cancel a reservation
     */
    public function cancelReservation(Request $request, $reservationId): JsonResponse
    {
        try {
            $reservation = $this->borrowingService->cancelReservation($reservationId);

            return response()->json([
                'success' => true,
                'message' => 'Reservation cancelled successfully',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling reservation',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/borrowings/fines
     * Get fines for current user
     */
    public function fines(Request $request): JsonResponse
    {
        try {
            $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : auth()->id();

            $fines = $this->borrowingService->getUserFines($userId);

            return response()->json([
                'success' => true,
                'message' => 'Fines retrieved successfully',
                'data' => [
                    'fines' => $fines,
                    'total_unpaid' => $fines->where('status', 'unpaid')->sum('amount'),
                    'total_paid' => $fines->where('status', 'paid')->sum('amount'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fines',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/borrowings/fines/{fine}/pay
     * Pay a fine
     */
    public function payFine(Request $request, $fineId): JsonResponse
    {
        try {
            $request->validate([
                'payment_method' => 'nullable|in:cash,card,online',
            ]);

            $fine = $this->borrowingService->payFine($fineId, $request->payment_method ?? 'cash');

            return response()->json([
                'success' => true,
                'message' => 'Fine paid successfully',
                'data' => $fine,
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
                'message' => 'Error paying fine',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/borrowings/overdue
     * Get overdue borrowings (Staff only)
     */
    public function overdue(): JsonResponse
    {
        try {
            $overdue = $this->borrowingService->getOverdueBorrowings();

            return response()->json([
                'success' => true,
                'message' => 'Overdue borrowings retrieved successfully',
                'data' => $overdue,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving overdue borrowings',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/borrowings/availability/{book}
     * Check book availability
     */
    public function availability(Request $request, $bookId): JsonResponse
    {
        try {
            $availability = $this->borrowingService->getBookAvailability($bookId);

            return response()->json([
                'success' => true,
                'message' => 'Book availability retrieved successfully',
                'data' => $availability,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving book availability',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}

