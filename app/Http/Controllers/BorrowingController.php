<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Fine;
use App\Models\Reservation;
use App\Models\User;
use App\States\Borrowing\BorrowingContext;
use App\Observers\Reservation\ReservationSubject;
use App\Observers\Reservation\EmailNotificationObserver;
use App\Observers\Reservation\LoggingObserver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * BorrowingController - Uses State Design Pattern
 *
 * All 5 core features implement State Pattern:
 * 1. Issue Book to Students (borrow)
 * 2. Return Book (return)
 * 3. Extend / Due Date Management (renew)
 * 4. View Borrow History (myHistory)
 * 5. Fine / Penalty Payment Management (myFines, payFine)
 */
class BorrowingController extends Controller
{
    protected float $fineRatePerDay = 0.50;
    protected int $defaultBorrowDays = 14;
    protected int $reservationExpiryDays = 3;

    /**
     * Show borrowing page with state information
     */
    public function index()
    {
        return view('borrowings.index');
    }

    /**
     * Create reservation subject with observers (Observer Pattern)
     */
    protected function createReservationSubject(Book $book): ReservationSubject
    {
        $subject = new ReservationSubject($book);
        $subject->attach(new EmailNotificationObserver());
        $subject->attach(new LoggingObserver());
        return $subject;
    }

    /**
     * 1. ISSUE BOOK TO STUDENTS - Using State Pattern
     *
     * State Transition: Available -> Borrowed
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
        $durationDays = $request->duration_days ?? $this->defaultBorrowDays;

        try {
            $borrowing = DB::transaction(function () use ($userId, $durationDays, $request) {
                $book = Book::findOrFail($request->book_id);
                $user = User::findOrFail($userId);

                // === STATE PATTERN: Create context and check state ===
                $context = new BorrowingContext($book);

                Log::info('State Pattern [BORROW]: Checking book state', [
                    'book_id' => $book->bookId,
                    'current_state' => $context->getStateName(),
                    'can_borrow' => $context->canBorrow()
                ]);

                // Check if book can be borrowed based on current state
                if (!$context->canBorrow()) {
                    throw new Exception("Cannot borrow book. Current state: {$context->getStateName()}");
                }

                // Business validations
                $this->validateBorrowingEligibility($user);

                // === STATE PATTERN: Perform state transition ===
                $context->borrow();

                // Create borrowing record
                $borrowing = Borrowing::create([
                    'user_id' => $userId,
                    'book_id' => $book->bookId,
                    'borrow_date' => now(),
                    'due_date' => now()->addDays($durationDays),
                    'status' => 'borrowed',
                ]);

                // Update book status
                $book->update(['status' => 'Borrowed']);

                // Fulfill reservation if exists
                $reservation = Reservation::where('user_id', $userId)
                    ->where('book_id', $book->bookId)
                    ->where('status', 'active')
                    ->first();

                if ($reservation) {
                    $reservation->update(['status' => 'fulfilled']);

                    // Observer Pattern: Notify about fulfilled reservation
                    $subject = $this->createReservationSubject($book);
                    $subject->notifyReservationFulfilled($reservation);
                }

                Log::info('State Pattern [BORROW]: State transition completed', [
                    'borrowing_id' => $borrowing->id,
                    'user_id' => $userId,
                    'book_id' => $book->bookId,
                    'new_state' => $context->getStateName()
                ]);

                return $borrowing->load(['user', 'book']);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book issued successfully',
                    'data' => $borrowing,
                    'state_pattern' => 'Transition: Available -> Borrowed'
                ]);
            }

            return redirect()->back()->with('success', 'Book issued successfully! Due date: ' . $borrowing->due_date->format('M d, Y'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 2. RETURN BOOK - Using State Pattern
     *
     * State Transitions:
     * - Borrowed -> Returned (normal)
     * - Overdue -> Returned (with fine calculation)
     */
    public function return(Request $request, $borrowingId)
    {
        try {
            $borrowing = DB::transaction(function () use ($borrowingId) {
                $borrowing = Borrowing::with('book', 'user')->findOrFail($borrowingId);
                $book = $borrowing->book;

                // === STATE PATTERN: Create context with current borrowing ===
                $context = new BorrowingContext($book, $borrowing);

                Log::info('State Pattern [RETURN]: Checking book state', [
                    'borrowing_id' => $borrowingId,
                    'current_state' => $context->getStateName(),
                    'can_return' => $context->canReturn()
                ]);

                if (!$context->canReturn()) {
                    throw new Exception("Cannot return book. Current state: {$context->getStateName()}");
                }

                // Calculate fine if overdue (handled by OverdueState)
                $fineAmount = 0;
                if ($borrowing->due_date->isPast()) {
                    $daysOverdue = now()->diffInDays($borrowing->due_date);
                    $fineAmount = $daysOverdue * $this->fineRatePerDay;

                    // Create fine record
                    if ($fineAmount > 0) {
                        Fine::create([
                            'user_id' => $borrowing->user_id,
                            'borrowing_id' => $borrowing->id,
                            'amount' => $fineAmount,
                            'reason' => "Overdue by {$daysOverdue} days",
                            'status' => 'unpaid',
                        ]);

                        Log::info('State Pattern [RETURN]: Fine created for overdue', [
                            'borrowing_id' => $borrowingId,
                            'days_overdue' => $daysOverdue,
                            'fine_amount' => $fineAmount
                        ]);
                    }
                }

                // === STATE PATTERN: Perform state transition ===
                $context->returnBook();

                // Update borrowing record
                $borrowing->update([
                    'return_date' => now(),
                    'status' => 'returned',
                    'fine_amount' => $fineAmount,
                ]);

                // Update book status
                $book->update(['status' => 'Available']);

                // === OBSERVER PATTERN: Notify waiting reservations ===
                $subject = $this->createReservationSubject($book);
                $subject->notifyBookAvailable();

                Log::info('State Pattern [RETURN]: State transition completed', [
                    'borrowing_id' => $borrowingId,
                    'fine_amount' => $fineAmount,
                    'new_state' => $context->getStateName()
                ]);

                return $borrowing->fresh(['user', 'book', 'fines']);
            });

            $message = 'Book returned successfully!';
            if ($borrowing->fine_amount > 0) {
                $message .= ' Fine amount: $' . number_format($borrowing->fine_amount, 2);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $borrowing,
                    'state_pattern' => 'Transition: Borrowed/Overdue -> Returned'
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 3. EXTEND / DUE DATE MANAGEMENT - Using State Pattern
     *
     * State: Borrowed -> Borrowed (extended)
     * Not allowed in: Overdue state, or if reservations exist
     */
    public function renew($borrowingId, Request $request)
    {
        $request->validate([
            'additional_days' => 'nullable|integer|min:1|max:14',
        ]);

        $additionalDays = $request->additional_days ?? 7;

        try {
            $borrowing = DB::transaction(function () use ($borrowingId, $additionalDays) {
                $borrowing = Borrowing::with('book')->findOrFail($borrowingId);
                $book = $borrowing->book;

                // === STATE PATTERN: Create context and check state ===
                $context = new BorrowingContext($book, $borrowing);

                Log::info('State Pattern [RENEW]: Checking book state', [
                    'borrowing_id' => $borrowingId,
                    'current_state' => $context->getStateName(),
                    'can_renew' => $context->canRenew()
                ]);

                if (!$context->canRenew()) {
                    throw new Exception("Cannot renew book. Current state: {$context->getStateName()}. Renewal only allowed for borrowed (not overdue) books.");
                }

                // Check for pending reservations
                if ($book->hasActiveReservation()) {
                    throw new Exception('Cannot renew. This book has pending reservations.');
                }

                // === STATE PATTERN: Perform renewal ===
                $context->renew();

                // Update borrowing record
                $newDueDate = $borrowing->due_date->addDays($additionalDays);
                $borrowing->update([
                    'due_date' => $newDueDate,
                    'notes' => ($borrowing->notes ? $borrowing->notes . "\n" : '') . 'Renewed on ' . now()->format('Y-m-d'),
                ]);

                Log::info('State Pattern [RENEW]: Due date extended', [
                    'borrowing_id' => $borrowingId,
                    'additional_days' => $additionalDays,
                    'new_due_date' => $newDueDate->format('Y-m-d'),
                    'state' => $context->getStateName()
                ]);

                return $borrowing->fresh(['user', 'book']);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Borrowing renewed successfully',
                    'data' => $borrowing,
                    'state_pattern' => 'State: Borrowed (extended due date)'
                ]);
            }

            return redirect()->back()->with('success', 'Borrowing renewed! New due date: ' . $borrowing->due_date->format('M d, Y'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 4. VIEW BORROW HISTORY - Using State Pattern for display
     *
     * Shows all borrowings with their current states
     */
    public function myHistory(Request $request)
    {
        // Get all borrowings with state information
        $borrowings = Borrowing::with(['book', 'user', 'fines'])
            ->when(!in_array(auth()->user()->role, ['Staff', 'Admin']), function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('borrow_date', 'desc')
            ->get()
            ->map(function ($borrowing) {
                // === STATE PATTERN: Get state for each borrowing ===
                $context = new BorrowingContext($borrowing->book, $borrowing);

                return [
                    'id' => $borrowing->id,
                    'user' => $borrowing->user,
                    'book' => $borrowing->book,
                    'borrow_date' => $borrowing->borrow_date,
                    'due_date' => $borrowing->due_date,
                    'return_date' => $borrowing->return_date,
                    'status' => $borrowing->status,
                    'fine_amount' => $borrowing->fine_amount,
                    'fines' => $borrowing->fines,
                    // State Pattern info
                    'current_state' => $context->getStateName(),
                    'can_return' => $context->canReturn(),
                    'can_renew' => $context->canRenew(),
                    'is_overdue' => $borrowing->status === 'borrowed' && $borrowing->due_date->isPast(),
                    'days_overdue' => $borrowing->status === 'borrowed' && $borrowing->due_date->isPast()
                        ? now()->diffInDays($borrowing->due_date) : 0,
                ];
            });

        Log::info('State Pattern [HISTORY]: Retrieved borrowing history with states', [
            'user_id' => auth()->id(),
            'total_records' => $borrowings->count()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $borrowings,
                'state_pattern' => 'Each record includes current state information'
            ]);
        }

        return view('borrowings.history', compact('borrowings'));
    }

    /**
     * 5. FINE / PENALTY PAYMENT MANAGEMENT - Using State Pattern
     *
     * Fines are generated from Overdue state transitions
     */
    public function myFines(Request $request)
    {
        // Get all fines with borrowing state info
        $fines = Fine::with(['borrowing.book', 'borrowing.user', 'user'])
            ->when(!in_array(auth()->user()->role, ['Staff', 'Admin']), function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($fine) {
                // === STATE PATTERN: Get state context ===
                $stateInfo = 'N/A';
                if ($fine->borrowing && $fine->borrowing->book) {
                    $context = new BorrowingContext($fine->borrowing->book, $fine->borrowing);
                    $stateInfo = $context->getStateName();
                }

                return [
                    'id' => $fine->id,
                    'user' => $fine->user,
                    'borrowing' => $fine->borrowing,
                    'amount' => $fine->amount,
                    'reason' => $fine->reason,
                    'status' => $fine->status,
                    'paid_date' => $fine->paid_date,
                    'payment_method' => $fine->payment_method,
                    'created_at' => $fine->created_at,
                    // State Pattern info
                    'borrowing_state' => $stateInfo,
                    'fine_origin' => 'Generated from Overdue state transition'
                ];
            });

        // Calculate totals
        $totalUnpaid = $fines->where('status', 'unpaid')->sum('amount');
        $totalPaid = $fines->where('status', 'paid')->sum('amount');

        Log::info('State Pattern [FINES]: Retrieved fines with state info', [
            'user_id' => auth()->id(),
            'total_unpaid' => $totalUnpaid,
            'total_paid' => $totalPaid
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'fines' => $fines,
                    'total_unpaid' => $totalUnpaid,
                    'total_paid' => $totalPaid,
                ],
                'state_pattern' => 'Fines originate from Overdue state during return'
            ]);
        }

        return view('borrowings.fines', [
            'fines' => $fines,
            'totalUnpaid' => $totalUnpaid,
            'totalPaid' => $totalPaid,
        ]);
    }

    /**
     * 5b. PAY FINE - State Pattern tracks fine payments
     */
    public function payFine(Request $request, $fineId)
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,online',
        ]);

        try {
            $fine = DB::transaction(function () use ($fineId, $request) {
                $fine = Fine::with('borrowing.book')->findOrFail($fineId);

                if ($fine->status === 'paid') {
                    throw new Exception('This fine has already been paid.');
                }

                // Get state context for logging
                $stateInfo = 'N/A';
                if ($fine->borrowing && $fine->borrowing->book) {
                    $context = new BorrowingContext($fine->borrowing->book, $fine->borrowing);
                    $stateInfo = $context->getStateName();
                }

                // Update fine as paid
                $fine->update([
                    'status' => 'paid',
                    'paid_date' => now(),
                    'payment_method' => $request->payment_method ?? 'cash',
                ]);

                // Update borrowing fine_paid status
                if ($fine->borrowing) {
                    $unpaidFinesForBorrowing = Fine::where('borrowing_id', $fine->borrowing_id)
                        ->where('status', 'unpaid')
                        ->count();

                    if ($unpaidFinesForBorrowing === 0) {
                        $fine->borrowing->update(['fine_paid' => true]);
                    }
                }

                Log::info('State Pattern [PAY_FINE]: Fine paid', [
                    'fine_id' => $fineId,
                    'user_id' => $fine->user_id,
                    'amount' => $fine->amount,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'borrowing_state' => $stateInfo
                ]);

                return $fine->fresh(['borrowing.book', 'user']);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fine paid successfully',
                    'data' => $fine,
                    'state_pattern' => 'Fine cleared - user can now borrow again'
                ]);
            }

            return redirect()->back()->with('success', 'Fine of $' . number_format($fine->amount, 2) . ' paid successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reserve a book (Observer Pattern)
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
        $expiryDays = $request->expiry_days ?? $this->reservationExpiryDays;

        try {
            $reservation = DB::transaction(function () use ($userId, $expiryDays, $request) {
                $book = Book::findOrFail($request->book_id);
                $user = User::findOrFail($userId);

                // === STATE PATTERN: Check if book is borrowed ===
                $context = new BorrowingContext($book);
                if ($context->canBorrow()) {
                    throw new Exception('Book is available. Please borrow it directly.');
                }

                // Validate reservation eligibility
                $this->validateReservationEligibility($user, $book);

                // Get queue position
                $queuePosition = Reservation::where('book_id', $book->bookId)
                    ->where('status', 'active')
                    ->count() + 1;

                $reservation = Reservation::create([
                    'user_id' => $userId,
                    'book_id' => $book->bookId,
                    'reservation_date' => now(),
                    'expiry_date' => now()->addDays($expiryDays),
                    'status' => 'active',
                    'queue_position' => $queuePosition,
                ]);

                Log::info('Reservation created', [
                    'reservation_id' => $reservation->id,
                    'user_id' => $userId,
                    'book_id' => $book->bookId,
                    'queue_position' => $queuePosition
                ]);

                return $reservation->load(['user', 'book']);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book reserved successfully',
                    'data' => $reservation,
                ]);
            }

            return redirect()->back()->with('success', 'Book reserved successfully! Expires: ' . $reservation->expiry_date->format('M d, Y'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel reservation
     */
    public function cancelReservation(Request $request, $reservationId)
    {
        try {
            $reservation = Reservation::with('book')->findOrFail($reservationId);

            if ($reservation->status !== 'active') {
                throw new Exception('This reservation is not active.');
            }

            $reservation->update(['status' => 'cancelled']);

            // Update queue positions
            Reservation::where('book_id', $reservation->book_id)
                ->where('status', 'active')
                ->where('queue_position', '>', $reservation->queue_position)
                ->decrement('queue_position');

            // Check if book is available and notify
            $context = new BorrowingContext($reservation->book);
            if ($context->canBorrow()) {
                $subject = $this->createReservationSubject($reservation->book);
                $subject->notifyBookAvailable();
            }

            Log::info('Reservation cancelled', [
                'reservation_id' => $reservationId,
                'user_id' => $reservation->user_id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reservation cancelled successfully',
                    'data' => $reservation,
                ]);
            }

            return redirect()->back()->with('success', 'Reservation cancelled successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Check book availability with state info
     */
    public function checkAvailability($bookId)
    {
        try {
            $book = Book::with(['activeBorrowing.user', 'activeReservations.user'])
                ->findOrFail($bookId);

            // === STATE PATTERN: Get current state ===
            $context = new BorrowingContext($book);

            return response()->json([
                'success' => true,
                'data' => [
                    'book' => $book,
                    'current_state' => $context->getStateName(),
                    'can_borrow' => $context->canBorrow(),
                    'can_return' => $context->canReturn(),
                    'can_renew' => $context->canRenew(),
                    'is_available' => $context->canBorrow(),
                    'current_borrower' => $book->activeBorrowing?->user,
                    'due_date' => $book->activeBorrowing?->due_date,
                    'reservation_count' => $book->activeReservations->count(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Get all overdue borrowings (Staff only)
     */
    public function overdueList()
    {
        $overdue = Borrowing::where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->with(['user', 'book'])
            ->get()
            ->map(function ($borrowing) {
                $context = new BorrowingContext($borrowing->book, $borrowing);
                return [
                    'borrowing' => $borrowing,
                    'current_state' => $context->getStateName(),
                    'days_overdue' => now()->diffInDays($borrowing->due_date),
                    'estimated_fine' => now()->diffInDays($borrowing->due_date) * $this->fineRatePerDay,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $overdue,
            'state_pattern' => 'All items in Overdue state'
        ]);
    }

    /**
     * Validate user eligibility to borrow
     */
    protected function validateBorrowingEligibility(User $user): void
    {
        $activeCount = $user->activeBorrowings()->count();
        $maxLimit = $user->isStudent() ? 3 : 5;

        if ($activeCount >= $maxLimit) {
            throw new Exception("Maximum borrowing limit ({$maxLimit} books) reached.");
        }

        if ($user->getTotalUnpaidFines() > 0) {
            throw new Exception('Please pay outstanding fines before borrowing.');
        }
    }

    /**
     * Validate user eligibility to reserve
     */
    protected function validateReservationEligibility(User $user, Book $book): void
    {
        $bookHasReservation = Reservation::where('book_id', $book->bookId)
            ->where('status', 'active')
            ->exists();

        if ($bookHasReservation) {
            throw new Exception('This book already has an active reservation.');
        }

        $userHasReservation = Reservation::where('user_id', $user->id)
            ->where('book_id', $book->bookId)
            ->where('status', 'active')
            ->exists();

        if ($userHasReservation) {
            throw new Exception('You already have an active reservation for this book.');
        }

        if ($user->activeReservations()->count() >= 3) {
            throw new Exception('Maximum 3 active reservations allowed.');
        }
    }
}
