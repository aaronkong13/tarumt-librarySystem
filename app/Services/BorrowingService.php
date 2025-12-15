<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\Fine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BorrowingService
{
    protected $fineRatePerDay = 0.50;
    protected $defaultBorrowDays = 14;
    protected $reservationExpiryDays = 3;

    /**
     * Borrow a book
     */
    public function borrowBook($userId, $bookId, $durationDays = null)
    {
        $durationDays = $durationDays ?? $this->defaultBorrowDays;

        return DB::transaction(function () use ($userId, $bookId, $durationDays) {
            $book = Book::findOrFail($bookId);
            $user = User::findOrFail($userId);

            // Check if book is available
            if ($book->isBorrowed()) {
                Log::warning('Borrow failed: Book already borrowed', [
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'book_title' => $book->title
                ]);
                throw new Exception('Book is currently borrowed by someone else.');
            }

            if ($book->status !== 'Available') {
                Log::warning('Borrow failed: Book not available', [
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'book_status' => $book->status
                ]);
                throw new Exception('Book is not available for borrowing.');
            }

            // Check user's borrowing limit
            $activeCount = $user->activeBorrowings()->count();
            $maxLimit = $user->isStudent() ? 3 : 5;

            if ($activeCount >= $maxLimit) {
                Log::warning('Borrow failed: Limit reached', [
                    'user_id' => $userId,
                    'active_count' => $activeCount,
                    'max_limit' => $maxLimit
                ]);
                throw new Exception("You have reached the maximum borrowing limit ({$maxLimit} books).");
            }

            // Check unpaid fines
            if ($user->getTotalUnpaidFines() > 0) {
                Log::warning('Borrow failed: Unpaid fines', [
                    'user_id' => $userId,
                    'unpaid_fines' => $user->getTotalUnpaidFines()
                ]);
                throw new Exception('Please pay your outstanding fines before borrowing.');
            }

            // Create borrowing record
            $borrowing = Borrowing::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'borrow_date' => now(),
                'due_date' => now()->addDays($durationDays),
                'status' => 'borrowed',
            ]);

            // Update book status
            $book->update(['status' => 'Borrowed']);

            // Fulfill reservation if exists
            Reservation::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->where('status', 'active')
                ->update(['status' => 'fulfilled']);

            Log::info('Book borrowed successfully', [
                'borrowing_id' => $borrowing->id,
                'user_id' => $userId,
                'user_name' => $user->name,
                'book_id' => $bookId,
                'book_title' => $book->title,
                'due_date' => $borrowing->due_date->format('Y-m-d'),
                'processed_by' => auth()->id()
            ]);

            return $borrowing->load(['user', 'book']);
        });
    }

    /**
     * Return a book
     */
    public function returnBook($borrowingId)
    {
        return DB::transaction(function () use ($borrowingId) {
            $borrowing = Borrowing::with('book')->findOrFail($borrowingId);

            if ($borrowing->status === 'returned') {
                Log::warning('Return failed: Already returned', ['borrowing_id' => $borrowingId]);
                throw new Exception('This book has already been returned.');
            }

            // Calculate fine if overdue
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

                    Log::info('Fine created for overdue return', [
                        'borrowing_id' => $borrowingId,
                        'user_id' => $borrowing->user_id,
                        'days_overdue' => $daysOverdue,
                        'fine_amount' => $fineAmount
                    ]);
                }
            }

            // Update borrowing
            $borrowing->update([
                'return_date' => now(),
                'status' => 'returned',
                'fine_amount' => $fineAmount,
            ]);

            // Update book status
            $borrowing->book->update(['status' => 'Available']);

            // Notify next reservation
            $this->notifyNextReservation($borrowing->book_id);

            Log::info('Book returned successfully', [
                'borrowing_id' => $borrowingId,
                'user_id' => $borrowing->user_id,
                'book_id' => $borrowing->book_id,
                'book_title' => $borrowing->book->title,
                'fine_amount' => $fineAmount,
                'processed_by' => auth()->id()
            ]);

            return $borrowing->load(['user', 'book']);
        });
    }

    /**
     * Reserve a book
     */
    public function reserveBook($userId, $bookId, $expiryDays = null)
    {
        $expiryDays = $expiryDays ?? $this->reservationExpiryDays;

        return DB::transaction(function () use ($userId, $bookId, $expiryDays) {
            $book = Book::findOrFail($bookId);
            $user = User::findOrFail($userId);

            // Check if book is borrowed
            if (!$book->isBorrowed()) {
                throw new Exception('Book is available. Please borrow it directly.');
            }

            // Check if book already has an active reservation (only 1 allowed per book)
            $bookHasReservation = Reservation::where('book_id', $bookId)
                ->where('status', 'active')
                ->exists();

            if ($bookHasReservation) {
                throw new Exception('This book already has an active reservation. Only one reservation per book is allowed.');
            }

            // Check if user already has reservation for this book
            $userHasReservation = Reservation::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->where('status', 'active')
                ->exists();

            if ($userHasReservation) {
                throw new Exception('This user already has an active reservation for this book.');
            }

            // Check reservation limit per user
            $activeReservations = $user->activeReservations()->count();
            if ($activeReservations >= 3) {
                throw new Exception('This user has reached the maximum of 3 active reservations.');
            }

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

            Log::info('Book reserved successfully', [
                'reservation_id' => $reservation->id,
                'user_id' => $userId,
                'user_name' => $user->name,
                'book_id' => $bookId,
                'book_title' => $book->title,
                'expiry_date' => $reservation->expiry_date->format('Y-m-d'),
                'processed_by' => auth()->id()
            ]);

            return $reservation->load(['user', 'book']);
        });
    }

    /**
     * Cancel reservation
     */
    public function cancelReservation($reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->status !== 'active') {
            throw new Exception('This reservation is not active.');
        }

        $reservation->update(['status' => 'cancelled']);

        // Update queue positions
        Reservation::where('book_id', $reservation->book_id)
            ->where('status', 'active')
            ->where('queue_position', '>', $reservation->queue_position)
            ->decrement('queue_position');

        Log::info('Reservation cancelled', [
            'reservation_id' => $reservationId,
            'user_id' => $reservation->user_id,
            'book_id' => $reservation->book_id,
            'cancelled_by' => auth()->id()
        ]);

        return $reservation;
    }

    /**
     * Renew a borrowing
     */
    public function renewBorrowing($borrowingId, $additionalDays = 7)
    {
        $borrowing = Borrowing::with('book')->findOrFail($borrowingId);

        if ($borrowing->status !== 'borrowed') {
            throw new Exception('Only active borrowings can be renewed.');
        }

        // Check for pending reservations
        $hasReservations = $borrowing->book->activeReservations()->exists();
        if ($hasReservations) {
            throw new Exception('Cannot renew. This book has pending reservations.');
        }

        $borrowing->update([
            'due_date' => Carbon::parse($borrowing->due_date)->addDays($additionalDays),
            'notes' => ($borrowing->notes ? $borrowing->notes . "\n" : '') . 'Renewed on ' . now()->format('Y-m-d'),
        ]);

        return $borrowing->load(['user', 'book']);
    }

    /**
     * Pay fine
     */
    public function payFine($fineId, $paymentMethod = 'cash')
    {
        $fine = Fine::findOrFail($fineId);

        if ($fine->status === 'paid') {
            throw new Exception('This fine has already been paid.');
        }

        $fine->markAsPaid($paymentMethod);

        // Update borrowing fine_paid status
        $borrowing = $fine->borrowing;
        $unpaidFinesForBorrowing = Fine::where('borrowing_id', $borrowing->id)
            ->where('status', 'unpaid')
            ->count();

        if ($unpaidFinesForBorrowing === 0) {
            $borrowing->update(['fine_paid' => true]);
        }

        return $fine;
    }

    /**
     * Notify next person in reservation queue
     */
    protected function notifyNextReservation($bookId)
    {
        $nextReservation = Reservation::where('book_id', $bookId)
            ->where('status', 'active')
            ->orderBy('queue_position')
            ->first();

        if ($nextReservation) {
            // Extend expiry for pickup
            $nextReservation->update([
                'expiry_date' => now()->addDays($this->reservationExpiryDays),
                'notes' => 'Book is now available for pickup!',
            ]);
        }
    }

    /**
     * Get user's borrowing history
     */
    public function getUserBorrowingHistory($userId)
    {
        return Borrowing::byUser($userId)
            ->with(['book'])
            ->orderBy('borrow_date', 'desc')
            ->get();
    }

    /**
     * Get user's active borrowings
     */
    public function getUserActiveBorrowings($userId)
    {
        return Borrowing::byUser($userId)
            ->active()
            ->with(['book'])
            ->get();
    }

    /**
     * Get overdue borrowings
     */
    public function getOverdueBorrowings()
    {
        return Borrowing::where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->with(['user', 'book'])
            ->get();
    }

    /**
     * Get user's fines
     */
    public function getUserFines($userId)
    {
        return Fine::byUser($userId)
            ->with(['borrowing.book'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get book availability info
     */
    public function getBookAvailability($bookId)
    {
        $book = Book::with(['activeBorrowing.user', 'activeReservations.user'])
            ->findOrFail($bookId);

        return [
            'book' => $book,
            'is_available' => !$book->isBorrowed() && $book->status === 'Available',
            'is_borrowed' => $book->isBorrowed(),
            'current_borrower' => $book->activeBorrowing?->user,
            'due_date' => $book->activeBorrowing?->due_date,
            'reservation_count' => $book->activeReservations->count(),
            'reservation_queue' => $book->activeReservations,
        ];
    }
}
