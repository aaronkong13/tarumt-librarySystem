<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\User;
use App\States\Borrowing\BorrowingContext;
use App\States\Borrowing\AvailableState;
use App\States\Borrowing\BorrowedState;
use App\Observers\Reservation\ReservationSubject;
use App\Observers\Reservation\EmailNotificationObserver;
use App\Observers\Reservation\LoggingObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BookBorrowingStateService
{
    protected float $fineRatePerDay = 0.50;
    protected int $defaultBorrowDays = 14;
    protected int $reservationExpiryDays = 3;

    /**
     * Create reservation subject with observers attached
     */
    protected function createReservationSubject(Book $book): ReservationSubject
    {
        $subject = new ReservationSubject($book);

        // Attach observers (Observer Pattern)
        $subject->attach(new EmailNotificationObserver());
        $subject->attach(new LoggingObserver());

        return $subject;
    }

    /**
     * Borrow a book using State Pattern
     */
    public function borrowBook($userId, $bookId, $durationDays = null)
    {
        $durationDays = $durationDays ?? $this->defaultBorrowDays;

        return DB::transaction(function () use ($userId, $bookId, $durationDays) {
            $book = Book::findOrFail($bookId);
            $user = User::findOrFail($userId);

            // Create borrowing context (State Pattern)
            $context = new BorrowingContext($book);

            Log::info('State Pattern: Checking borrowing state', [
                'book_id' => $bookId,
                'current_state' => $context->getStateName(),
                'can_borrow' => $context->canBorrow()
            ]);

            // Check if book can be borrowed based on current state
            if (!$context->canBorrow()) {
                throw new Exception("Book cannot be borrowed. Current state: {$context->getStateName()}");
            }

            // Business logic validations
            $this->validateBorrowingEligibility($user);

            // Perform state transition
            $context->borrow();

            // Create borrowing record
            $borrowing = Borrowing::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'borrow_date' => now(),
                'due_date' => now()->addDays($durationDays),
                'status' => 'borrowed',
            ]);

            // Check if user had a reservation for this book
            $reservation = Reservation::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->where('status', 'active')
                ->first();

            if ($reservation) {
                $reservation->update(['status' => 'fulfilled']);

                // Notify observers about fulfilled reservation (Observer Pattern)
                $subject = $this->createReservationSubject($book);
                $subject->notifyReservationFulfilled($reservation);
            }

            Log::info('State Pattern: Book borrowed successfully', [
                'borrowing_id' => $borrowing->id,
                'user_id' => $userId,
                'book_id' => $bookId,
                'new_state' => $context->getStateName()
            ]);

            return $borrowing->load(['user', 'book']);
        });
    }

    /**
     * Return a book using State Pattern
     */
    public function returnBook($borrowingId)
    {
        return DB::transaction(function () use ($borrowingId) {
            $borrowing = Borrowing::with('book', 'user')->findOrFail($borrowingId);
            $book = $borrowing->book;

            // Create borrowing context with current borrowing (State Pattern)
            $context = new BorrowingContext($book, $borrowing);

            Log::info('State Pattern: Checking return state', [
                'borrowing_id' => $borrowingId,
                'current_state' => $context->getStateName(),
                'can_return' => $context->canReturn()
            ]);

            if (!$context->canReturn()) {
                throw new Exception("Book cannot be returned. Current state: {$context->getStateName()}");
            }

            // Calculate fine before state transition
            $fineAmount = 0;
            if ($borrowing->due_date->isPast()) {
                $daysOverdue = now()->diffInDays($borrowing->due_date);
                $fineAmount = $daysOverdue * $this->fineRatePerDay;
            }

            // Perform state transition (handles fine creation if overdue)
            $context->returnBook();

            // Update borrowing record
            $borrowing->update([
                'return_date' => now(),
                'status' => 'returned',
                'fine_amount' => $fineAmount,
            ]);

            // Notify observers about book availability (Observer Pattern)
            $subject = $this->createReservationSubject($book);
            $subject->notifyBookAvailable();

            Log::info('State Pattern: Book returned successfully', [
                'borrowing_id' => $borrowingId,
                'fine_amount' => $fineAmount,
                'new_state' => $context->getStateName()
            ]);

            return $borrowing->fresh(['user', 'book', 'fines']);
        });
    }

    /**
     * Renew a borrowing using State Pattern
     */
    public function renewBorrowing($borrowingId, $additionalDays = 7)
    {
        return DB::transaction(function () use ($borrowingId, $additionalDays) {
            $borrowing = Borrowing::with('book')->findOrFail($borrowingId);
            $book = $borrowing->book;

            // Create borrowing context (State Pattern)
            $context = new BorrowingContext($book, $borrowing);

            Log::info('State Pattern: Checking renew state', [
                'borrowing_id' => $borrowingId,
                'current_state' => $context->getStateName(),
                'can_renew' => $context->canRenew()
            ]);

            if (!$context->canRenew()) {
                throw new Exception("Book cannot be renewed. Current state: {$context->getStateName()}");
            }

            // Check for reservations before allowing renewal
            if ($book->hasActiveReservation()) {
                throw new Exception('Cannot renew - book has pending reservations.');
            }

            // Perform renewal
            $context->renew();

            // Update borrowing record
            $borrowing->update([
                'due_date' => $borrowing->due_date->addDays($additionalDays)
            ]);

            Log::info('State Pattern: Book renewed successfully', [
                'borrowing_id' => $borrowingId,
                'new_due_date' => $borrowing->due_date->format('Y-m-d')
            ]);

            return $borrowing->fresh(['user', 'book']);
        });
    }

    /**
     * Reserve a book with Observer Pattern
     */
    public function reserveBook($userId, $bookId, $expiryDays = null)
    {
        $expiryDays = $expiryDays ?? $this->reservationExpiryDays;

        return DB::transaction(function () use ($userId, $bookId, $expiryDays) {
            $book = Book::findOrFail($bookId);
            $user = User::findOrFail($userId);

            // Create reservation subject (Observer Pattern)
            $subject = $this->createReservationSubject($book);

            // Check if book is actually borrowed
            $context = new BorrowingContext($book);
            if ($context->canBorrow()) {
                throw new Exception('Book is available. Please borrow it directly.');
            }

            // Check for existing reservations
            $this->validateReservationEligibility($user, $book);

            // Get queue position
            $queuePosition = Reservation::where('book_id', $bookId)
                ->where('status', 'active')
                ->count() + 1;

            $reservation = Reservation::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'reservation_date' => now(),
                'expiry_date' => now()->addDays($expiryDays),
                'status' => 'active',
                'queue_position' => $queuePosition,
            ]);

            Log::info('Observer Pattern: Reservation created', [
                'reservation_id' => $reservation->id,
                'user_id' => $userId,
                'book_id' => $bookId,
                'queue_position' => $queuePosition,
                'observers_attached' => 2  // EmailNotificationObserver, LoggingObserver
            ]);

            return $reservation->load(['user', 'book']);
        });
    }

    /**
     * Cancel reservation with Observer notifications
     */
    public function cancelReservation($reservationId)
    {
        $reservation = Reservation::with('book', 'user')->findOrFail($reservationId);

        if ($reservation->status !== 'active') {
            throw new Exception('This reservation is not active.');
        }

        $book = $reservation->book;
        $reservation->update(['status' => 'cancelled']);

        // Reorder queue positions
        Reservation::where('book_id', $reservation->book_id)
            ->where('status', 'active')
            ->where('queue_position', '>', $reservation->queue_position)
            ->decrement('queue_position');

        // Notify next person in queue if book is available
        $context = new BorrowingContext($book);
        if ($context->canBorrow()) {
            $subject = $this->createReservationSubject($book);
            $subject->notifyBookAvailable();
        }

        Log::info('Observer Pattern: Reservation cancelled', [
            'reservation_id' => $reservationId,
            'user_id' => $reservation->user_id,
            'book_id' => $reservation->book_id
        ]);

        return $reservation;
    }

    /**
     * Process expired reservations (to be called by scheduler)
     */
    public function processExpiredReservations()
    {
        $books = Book::whereHas('reservations', function ($query) {
            $query->where('status', 'active')
                  ->where('expiry_date', '<', now());
        })->get();

        foreach ($books as $book) {
            $subject = $this->createReservationSubject($book);
            $subject->notifyReservationExpired();
        }

        Log::info('Observer Pattern: Processed expired reservations', [
            'books_processed' => $books->count()
        ]);
    }

    /**
     * Send expiring soon notifications (to be called by scheduler)
     */
    public function notifyExpiringReservations()
    {
        $books = Book::whereHas('reservations', function ($query) {
            $query->where('status', 'active')
                  ->whereBetween('expiry_date', [now(), now()->addHours(24)]);
        })->get();

        foreach ($books as $book) {
            $subject = $this->createReservationSubject($book);
            $subject->notifyReservationExpiring();
        }

        Log::info('Observer Pattern: Sent expiring notifications', [
            'books_processed' => $books->count()
        ]);
    }

    /**
     * Validate user eligibility to borrow
     */
    protected function validateBorrowingEligibility(User $user): void
    {
        // Check borrowing limit
        $activeCount = $user->activeBorrowings()->count();
        $maxLimit = $user->isStudent() ? 3 : 5;

        if ($activeCount >= $maxLimit) {
            throw new Exception("Maximum borrowing limit ({$maxLimit} books) reached.");
        }

        // Check unpaid fines
        if ($user->getTotalUnpaidFines() > 0) {
            throw new Exception('Please pay outstanding fines before borrowing.');
        }
    }

    /**
     * Validate user eligibility to reserve
     */
    protected function validateReservationEligibility(User $user, Book $book): void
    {
        // Only 1 reservation per book allowed
        $bookHasReservation = Reservation::where('book_id', $book->bookId)
            ->where('status', 'active')
            ->exists();

        if ($bookHasReservation) {
            throw new Exception('This book already has an active reservation.');
        }

        // Check if user already has reservation for this book
        $userHasReservation = Reservation::where('user_id', $user->id)
            ->where('book_id', $book->bookId)
            ->where('status', 'active')
            ->exists();

        if ($userHasReservation) {
            throw new Exception('You already have an active reservation for this book.');
        }

        // Check reservation limit per user
        if ($user->activeReservations()->count() >= 3) {
            throw new Exception('Maximum 3 active reservations allowed.');
        }
    }

    /**
     * Get book state information
     */
    public function getBookState($bookId): array
    {
        $book = Book::findOrFail($bookId);
        $context = new BorrowingContext($book);

        return [
            'book_id' => $bookId,
            'state' => $context->getStateName(),
            'can_borrow' => $context->canBorrow(),
            'can_return' => $context->canReturn(),
            'can_renew' => $context->canRenew(),
        ];
    }
}
