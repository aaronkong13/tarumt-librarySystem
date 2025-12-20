<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BorrowingService;
use App\Services\FineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * BorrowingApiController - REST API for Borrowing & Fine Module
 *
 * Architecture matches BookApiController:
 * - Uses dependency injection for BorrowingService and FineService
 * - Provides JSON endpoints for borrowing and fine operations
 * - Delegates all business logic to service layer
 * - Can be consumed by mobile apps, frontend frameworks, or external services
 *
 * Integrated: FineApiController methods for unified API management
 */
class BorrowingApiController extends Controller
{
    private BorrowingService $borrowingService;
    private FineService $fineService;

    public function __construct(BorrowingService $borrowingService)
    {
        // Dependency injection - same pattern as BookApiController
        $this->borrowingService = $borrowingService;
        $this->fineService = new FineService();
    }

    // =====================================================================
    // BORROWING ENDPOINTS
    // =====================================================================

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

    // =====================================================================
    // FINE ENDPOINTS (Integrated from FineApiController)
    // =====================================================================

    /**
     * GET /api/borrowings/fines
     * Get all fines (staff) or user's fines (students)
     */
    public function fines(Request $request): JsonResponse
    {
        try {
            $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
            $status = $request->get('status');

            if ($isStaff) {
                $fines = $this->fineService->getAllFinesWithStates($status);
                $statistics = $this->fineService->getFineStatistics();
            } else {
                $fines = $this->fineService->getUserFinesWithStates(auth()->id(), $status);
                $statistics = $this->fineService->getUserFineSummary(auth()->id());
            }

            return response()->json([
                'success' => true,
                'message' => 'Fines retrieved successfully',
                'data' => [
                    'fines' => $fines,
                    'statistics' => $statistics,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving fines',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/borrowings/fines/{id}
     * Get single fine details
     */
    public function showFine($id): JsonResponse
    {
        try {
            $fine = $this->fineService->getFineById($id);

            return response()->json([
                'success' => true,
                'data' => $fine,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fine not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * POST /api/borrowings/fines/{id}/pay
     * Pay a fine
     */
    public function payFine(Request $request, $id): JsonResponse
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,online',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $fine = $this->fineService->payFine(
                $id,
                $request->payment_method ?? 'cash',
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Fine paid successfully',
                'data' => $fine,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error paying fine',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/borrowings/fines/pay-all
     * Pay all unpaid fines for current user
     */
    public function payAllFines(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,online',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : auth()->id();

            $result = $this->fineService->payAllUserFines(
                $userId,
                $request->payment_method ?? 'cash'
            );

            return response()->json([
                'success' => true,
                'message' => 'All fines paid successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error paying fines',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/borrowings/fines/{id}/waive
     * Waive a fine (Staff/Admin only)
     */
    public function waiveFine(Request $request, $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $fine = $this->fineService->waiveFine($id, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Fine waived successfully',
                'data' => $fine,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error waiving fine',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/borrowings/fines/statistics
     * Get fine statistics (Staff/Admin only)
     */
    public function fineStatistics(): JsonResponse
    {
        try {
            $statistics = $this->fineService->getFineStatistics();

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/borrowings/fines/process-overdue
     * Process all overdue borrowings (Staff/Admin only)
     */
    public function processOverdueFines(): JsonResponse
    {
        try {
            $results = $this->fineService->processOverdueFines();

            return response()->json([
                'success' => true,
                'message' => 'Overdue fines processed successfully',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing overdue fines',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/borrowings/fines/user/{userId}/summary
     * Get user's fine summary
     */
    public function userFineSummary($userId = null): JsonResponse
    {
        try {
            $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
            $targetUserId = $isStaff && $userId ? $userId : auth()->id();

            $summary = $this->fineService->getUserFineSummary($targetUserId);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/borrowings/fines/check-unpaid
     * Check if current user has unpaid fines
     */
    public function checkUnpaidFines(): JsonResponse
    {
        try {
            $hasUnpaid = $this->fineService->userHasUnpaidFines(auth()->id());
            $totalUnpaid = $this->fineService->getUserTotalUnpaidFines(auth()->id());

            return response()->json([
                'success' => true,
                'data' => [
                    'has_unpaid_fines' => $hasUnpaid,
                    'total_unpaid_amount' => $totalUnpaid,
                    'can_borrow' => !$hasUnpaid,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking unpaid fines',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/borrowings/fines/report
     * Generate fine report (Staff/Admin only)
     */
    public function fineReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $report = $this->fineService->generateFineReport(
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
