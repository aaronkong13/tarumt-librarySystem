<?php

namespace App\Http\Controllers;

use App\Services\BookBorrowingStateService;
use Illuminate\Http\Request;
use Exception;

/**
 * Controller using State and Observer Design Patterns
 *
 * State Pattern: Book borrowing states (Available, Borrowed, Overdue, Returned)
 * Observer Pattern: Reservation notifications when book becomes available
 */
class BookStateController extends Controller
{
    protected $stateService;

    public function __construct(BookBorrowingStateService $stateService)
    {
        $this->stateService = $stateService;
    }

    /**
     * Get current state of a book (State Pattern)
     */
    public function getBookState($bookId)
    {
        try {
            $stateInfo = $this->stateService->getBookState($bookId);

            return response()->json([
                'success' => true,
                'data' => $stateInfo,
                'pattern' => 'State Pattern'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Borrow a book using State Pattern
     */
    public function borrow(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        $request->validate([
            'book_id' => 'required|exists:books,bookId',
            'user_id' => $isStaff ? 'required|exists:users,id' : 'nullable',
            'duration_days' => 'nullable|integer|min:1|max:30',
        ], [
            'user_id.required' => 'Please enter the User ID of the student borrowing the book.',
        ]);

        $userId = $isStaff ? $request->user_id : auth()->id();

        try {
            $borrowing = $this->stateService->borrowBook(
                $userId,
                $request->book_id,
                $request->duration_days ?? 14
            );

            $message = 'Book borrowed successfully (State Pattern)';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $borrowing,
                    'pattern' => 'State Pattern - Transition from Available to Borrowed'
                ]);
            }

            return redirect()->back()->with('success', $message . '! Due date: ' . $borrowing->due_date->format('M d, Y'));
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
     * Return a book using State Pattern with Observer notifications
     */
    public function return(Request $request, $borrowingId)
    {
        try {
            $borrowing = $this->stateService->returnBook($borrowingId);

            $message = 'Book returned successfully (State Pattern)!';
            if ($borrowing->fine_amount > 0) {
                $message .= ' Fine amount: $' . number_format($borrowing->fine_amount, 2);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $borrowing,
                    'pattern' => 'State Pattern (state transition) + Observer Pattern (notification sent)'
                ]);
            }

            return redirect()->back()->with('success', $message);
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
     * Renew a borrowing using State Pattern
     */
    public function renew($borrowingId, Request $request)
    {
        $request->validate([
            'additional_days' => 'nullable|integer|min:1|max:14',
        ]);

        try {
            $borrowing = $this->stateService->renewBorrowing(
                $borrowingId,
                $request->additional_days ?? 7
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Borrowing renewed successfully (State Pattern)',
                    'data' => $borrowing,
                    'pattern' => 'State Pattern - Renewed while in Borrowed state'
                ]);
            }

            return redirect()->back()->with('success', 'Borrowing renewed! New due date: ' . $borrowing->due_date->format('M d, Y'));
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
     * Reserve a book using Observer Pattern
     */
    public function reserve(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        $request->validate([
            'book_id' => 'required|exists:books,bookId',
            'user_id' => $isStaff ? 'required|exists:users,id' : 'nullable',
            'expiry_days' => 'nullable|integer|min:1|max:7',
        ], [
            'user_id.required' => 'Please enter the User ID of the student reserving the book.',
        ]);

        $userId = $isStaff ? $request->user_id : auth()->id();

        try {
            $reservation = $this->stateService->reserveBook(
                $userId,
                $request->book_id,
                $request->expiry_days ?? 3
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book reserved successfully (Observer Pattern)',
                    'data' => $reservation,
                    'pattern' => 'Observer Pattern - User subscribed to book availability notifications'
                ]);
            }

            return redirect()->back()->with('success', 'Book reserved! You will be notified when available. Expires: ' . $reservation->expiry_date->format('M d, Y'));
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
     * Cancel reservation using Observer Pattern
     */
    public function cancelReservation(Request $request, $reservationId)
    {
        try {
            $reservation = $this->stateService->cancelReservation($reservationId);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reservation cancelled successfully (Observer Pattern)',
                    'data' => $reservation,
                    'pattern' => 'Observer Pattern - User unsubscribed from notifications'
                ]);
            }

            return redirect()->back()->with('success', 'Reservation cancelled successfully!');
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
     * Process expired reservations (for scheduler/cron)
     */
    public function processExpiredReservations()
    {
        try {
            $this->stateService->processExpiredReservations();

            return response()->json([
                'success' => true,
                'message' => 'Expired reservations processed (Observer Pattern)',
                'pattern' => 'Observer Pattern - Expiry notifications sent to observers'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send expiring soon notifications (for scheduler/cron)
     */
    public function notifyExpiringReservations()
    {
        try {
            $this->stateService->notifyExpiringReservations();

            return response()->json([
                'success' => true,
                'message' => 'Expiring notifications sent (Observer Pattern)',
                'pattern' => 'Observer Pattern - Expiring soon notifications sent to observers'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
