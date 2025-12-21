<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BorrowingService;
use App\Services\FineService;
use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

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
        $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);

        try {
            $request->validate([
                'book_id' => 'required|integer|exists:books,bookId',
                'user_id' => $isStaff ? 'required|integer|exists:users,id' : 'nullable',
                'duration_days' => 'nullable|integer|min:1|max:30',
            ]);

            $userId = $isStaff ? $request->user_id : Auth::id();
            $durationDays = $request->duration_days ?? null;

            $borrowing = $this->borrowingService->borrowBook($userId, $request->book_id, $durationDays);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book borrowed successfully',
                'data' => $borrowing,
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
                'timestamp' => now()->toIso8601String(),
                'message' => $message,
                'data' => $borrowing,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Borrowing renewed successfully',
                'data' => $borrowing,
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
            $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : Auth::id();

            $borrowings = $this->borrowingService->getUserBorrowingHistory($userId);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Borrowing history retrieved successfully',
                'data' => $borrowings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
            $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : Auth::id();

            $borrowings = $this->borrowingService->getUserActiveBorrowings($userId);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Active borrowings retrieved successfully',
                'data' => $borrowings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
        $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);

        try {
            $request->validate([
                'book_id' => 'required|integer|exists:books,bookId',
                'user_id' => $isStaff ? 'required|integer|exists:users,id' : 'nullable',
                'expiry_days' => 'nullable|integer|min:1|max:7',
            ]);

            $userId = $isStaff ? $request->user_id : Auth::id();
            $expiryDays = $request->expiry_days ?? null;

            $reservation = $this->borrowingService->reserveBook($userId, $request->book_id, $expiryDays);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book reserved successfully',
                'data' => $reservation,
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Reservation cancelled successfully',
                'data' => $reservation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Overdue borrowings retrieved successfully',
                'data' => $overdue,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Book availability retrieved successfully',
                'data' => $availability,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
            $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);
            $status = $request->get('status');

            if ($isStaff) {
                $fines = $this->fineService->getAllFinesWithStates($status);
                $statistics = $this->fineService->getFineStatistics();
            } else {
                $fines = $this->fineService->getUserFinesWithStates(Auth::id(), $status);
                $statistics = $this->fineService->getUserFineSummary(Auth::id());
            }

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Fines retrieved successfully',
                'data' => [
                    'fines' => $fines,
                    'statistics' => $statistics,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'data' => $fine,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Fine paid successfully',
                'data' => $fine,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
            $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);
            $userId = $isStaff && $request->user_id ? $request->user_id : Auth::id();

            $result = $this->fineService->payAllUserFines(
                $userId,
                $request->payment_method ?? 'cash'
            );

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'All fines paid successfully',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Fine waived successfully',
                'data' => $fine,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'data' => $statistics,
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
     * POST /api/borrowings/fines/process-overdue
     * Process all overdue borrowings (Staff/Admin only)
     */
    public function processOverdueFines(): JsonResponse
    {
        try {
            $results = $this->fineService->processOverdueFines();

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Overdue fines processed successfully',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
            $isStaff = in_array(Auth::user()->role, ['Staff', 'Admin']);
            $targetUserId = $isStaff && $userId ? $userId : Auth::id();

            $summary = $this->fineService->getUserFineSummary($targetUserId);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
            $hasUnpaid = $this->fineService->userHasUnpaidFines(Auth::id());
            $totalUnpaid = $this->fineService->getUserTotalUnpaidFines(Auth::id());

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'has_unpaid_fines' => $hasUnpaid,
                    'total_unpaid_amount' => $totalUnpaid,
                    'can_borrow' => !$hasUnpaid,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error generating report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/borrowings/books/{bookId}/stats
     * Get borrowing statistics and history for a specific book
     */
    public function bookStats($bookId): JsonResponse
    {
        try {
            $stats = $this->borrowingService->getBookBorrowingStats($bookId);

            return response()->json([
                'success' => true,
                'timestamp' => $stats['timestamp'],
                'message' => 'Book borrowing statistics retrieved successfully',
                'data' => [
                    'total_borrows' => $stats['total_borrows'],
                    'completed_borrows' => $stats['completed_borrows'],
                    'currently_borrowed' => $stats['currently_borrowed'],
                    'unique_borrowers' => $stats['unique_borrowers'],
                    'history' => $stats['history']->map(function($record) {
                        return [
                            'id' => $record->id,  // Changed from borrowingId
                            'user' => [
                                'id' => $record->user->id,  // Changed from userId
                                'name' => $record->user->name,
                                'email' => $record->user->email,
                            ],
                            'borrow_date' => $record->borrow_date,
                            'return_date' => $record->return_date,
                            'due_date' => $record->due_date,
                            'status' => $record->return_date ? 'Returned' : 'Borrowed',
                            'is_overdue' => $record->due_date && !$record->return_date && now()->gt($record->due_date),
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving borrowing statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/borrowings/users/{userId}/history
     * Get borrowing history for a specific user (for user management)
     */
    public function getUserBorrowings($userId): JsonResponse
    {
        try {
            $borrowings = $this->borrowingService->getUserBorrowingHistory($userId);

            // Process borrowings to make them JSON serializable
            $processedBorrowings = $borrowings->map(function ($borrowing) {
                return [
                    'id' => $borrowing->id,
                    'user_id' => $borrowing->user_id,
                    'book_id' => $borrowing->book_id,
                    'book_title' => $borrowing->book->title,
                    'book_author' => $borrowing->book->author,
                    'book_isbn' => $borrowing->book->isbn,
                    'book_cover_image' => $borrowing->book->cover_image ? base64_encode($borrowing->book->cover_image) : null,
                    'borrow_date' => $borrowing->borrow_date,
                    'due_date' => $borrowing->due_date,
                    'return_date' => $borrowing->return_date,
                    'status' => $borrowing->status,
                    'fine_amount' => $borrowing->fine_amount,
                    'notes' => $borrowing->notes,
                    'created_at' => $borrowing->created_at,
                    'updated_at' => $borrowing->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'User borrowings retrieved successfully',
                'data' => $processedBorrowings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving user borrowings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================================
    // REMINDER ENDPOINTS
    // =====================================================================

    /**
     * GET /api/reminders/statistics
     * Get reminder statistics and overview
     */
    public function reminderStatistics(): JsonResponse
    {
        $reminderService = new ReminderService();
        $stats = $reminderService->getReminderStatistics();

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => $stats,
            'message' => 'Reminder statistics retrieved successfully',
        ]);
    }

    /**
     * POST /api/reminders/send
     * Manually trigger due book reminders
     */
    public function sendReminders(Request $request): JsonResponse
    {
        $dryRun = $request->boolean('dry_run', false);

        try {
            $exitCode = Artisan::call('reminders:send-due-books', [
                '--dry-run' => $dryRun,
            ]);

            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'timestamp' => now()->toIso8601String(),
                    'message' => $dryRun
                        ? 'Dry run completed successfully. No emails were sent.'
                        : 'Reminders sent successfully.',
                    'output' => $output,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process reminders',
                'output' => $output,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing reminders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/reminders/preview
     * Preview what reminders would be sent without actually sending
     */
    public function previewReminders(): JsonResponse
    {
        $reminderService = new ReminderService();
        $dueReminders = [];
        $overdueReminders = [];

        foreach ([3, 1, 0] as $daysBeforeDue) {
            $borrowings = $reminderService->getBorrowingsDueSoon($daysBeforeDue);

            foreach ($borrowings as $borrowing) {
                $dueReminders[] = [
                    'borrowing_id' => $borrowing->id,
                    'user' => [
                        'id' => $borrowing->user->id,
                        'name' => $borrowing->user->name,
                        'email' => $borrowing->user->email,
                    ],
                    'book' => [
                        'id' => $borrowing->book->bookId,
                        'title' => $borrowing->book->title,
                        'author' => $borrowing->book->author,
                    ],
                    'due_date' => $borrowing->due_date->toDateString(),
                    'days_until_due' => $daysBeforeDue,
                    'reminder_type' => $daysBeforeDue === 0 ? 'due_today' : 'due_soon',
                ];
            }
        }

        foreach ([1, 3, 7, 14, 21, 28, 35, 42, 49] as $daysOverdue) {
            $borrowings = $reminderService->getOverdueBorrowings($daysOverdue);

            foreach ($borrowings as $borrowing) {
                $fineAmount = $reminderService->calculateFineAmount($borrowing, $daysOverdue);

                $overdueReminders[] = [
                    'borrowing_id' => $borrowing->id,
                    'user' => [
                        'id' => $borrowing->user->id,
                        'name' => $borrowing->user->name,
                        'email' => $borrowing->user->email,
                    ],
                    'book' => [
                        'id' => $borrowing->book->bookId,
                        'title' => $borrowing->book->title,
                        'author' => $borrowing->book->author,
                    ],
                    'due_date' => $borrowing->due_date->toDateString(),
                    'days_overdue' => $daysOverdue,
                    'fine_amount' => $fineAmount,
                    'reminder_type' => 'overdue',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'due_reminders' => $dueReminders,
                'overdue_reminders' => $overdueReminders,
                'summary' => [
                    'total_due_reminders' => count($dueReminders),
                    'total_overdue_reminders' => count($overdueReminders),
                    'total' => count($dueReminders) + count($overdueReminders),
                ],
            ],
            'message' => 'Preview generated successfully',
        ]);
    }

    /**
     * GET /api/reminders/configuration
     * Get the current reminder configuration
     */
    public function reminderConfiguration(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'fine_rate_per_day' => config('library.fine_rate_per_day'),
                'max_fine' => config('library.max_fine'),
                'reminder_days_before' => config('library.reminder_days_before'),
                'overdue_reminder_days' => config('library.overdue_reminder_days'),
                'reminder_send_time' => config('library.reminder_send_time'),
            ],
            'message' => 'Configuration retrieved successfully',
        ]);
    }
}
