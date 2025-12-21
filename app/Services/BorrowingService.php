<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\Fine;
use App\Models\User;
use App\Services\ReservationService;
use App\Notifications\BookBorrowedNotification;
use Illuminate\Support\Facades\Http;
use App\States\Borrowing\BorrowingContext;
use App\Observers\Reservation\ReservationSubject;
use App\Observers\Reservation\EmailNotificationObserver;
use App\Observers\Reservation\LoggingObserver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * BorrowingService - Centralized business logic for borrowing operations
 *
 * Uses State Pattern and Observer Pattern for borrowing workflow.
 * All business logic is here - controllers just handle HTTP concerns.
 *
 * MODULE SEPARATION:
 * - Borrowing Module (this) ≠ Book Module
 * - Uses HTTP calls to Book API endpoints (localhost:8001/api/books)
 * - No BookApiClient needed - direct HTTP calls to ApiControllers
 */
class BorrowingService
{
    protected $fineRatePerDay = 0.50;
    protected $defaultBorrowDays = -2;
    protected $reservationExpiryDays = 3;

    public function __construct()
    {
        // No dependencies needed - uses direct HTTP calls to API endpoints
    }

    /**
     * Borrow a book - Using State Pattern
     */
    public function borrowBook($userId, $bookId, $durationDays = null)
    {
        $durationDays = $durationDays ?? $this->defaultBorrowDays;

        return DB::transaction(function () use ($userId, $bookId, $durationDays) {
            // Get book directly (same application - direct access OK)
            $book = Book::find($bookId);
            if (!$book) {
                throw new Exception("Book not found");
            }

            // Get user directly (same application - direct access OK)
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("User not found");
            }

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

            // Create borrowing record (Borrowing module - direct access OK)
            $borrowing = Borrowing::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'borrow_date' => now(),
                'due_date' => now()->addDays($durationDays),
                'status' => 'borrowed',
            ]);

            // Update book status directly (same application - direct access OK)
            $book->status = 'Borrowed';
            $book->save();

            // Fulfill reservation if exists
            $reservationService = app(ReservationService::class);
            $reservationService->fulfillReservation($userId, $bookId);

            Log::info('State Pattern [BORROW]: State transition completed', [
                'borrowing_id' => $borrowing->id,
                'user_id' => $userId,
                'book_id' => $bookId,
                'new_state' => $context->getStateName()
            ]);

            // Send email notification to user
            $borrowing->load(['user', 'book']);
            if ($user->email_verified_at) {
                try {
                    $user->notify(new BookBorrowedNotification($borrowing));
                    Log::info('Book borrowed notification sent', [
                        'borrowing_id' => $borrowing->id,
                        'user_email' => $user->email,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send book borrowed notification', [
                        'borrowing_id' => $borrowing->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $borrowing;
        });
    }

    /**
     * Return a book - Using State Pattern
     */
    public function returnBook($borrowingId)
    {
        return DB::transaction(function () use ($borrowingId) {
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

            // === OBSERVER PATTERN: Check for reservations ===
            $hasReservation = Reservation::where('book_id', $book->bookId)
                ->whereIn('status', ['waiting', Reservation::STATUS_WAITING])
                ->exists();

            $apiUrl = config('app.api_url', config('app.url'));
            $newStatus = 'Available';

            if ($hasReservation) {
                // Book stays as 'Reserved' for the next person in queue
                $reservationService = app(ReservationService::class);
                $nextReservation = $reservationService->notifyNextInQueue($book);

                if ($nextReservation) {
                    $newStatus = 'Reserved';
                    Log::info('Book reserved for next user in queue', [
                        'book_id' => $book->bookId,
                        'reservation_id' => $nextReservation->id,
                        'user_id' => $nextReservation->user_id
                    ]);
                }
            }

            // Update book status via API (cross-module: Borrowing → Book)
            try {
                Http::put("{$apiUrl}/api/books/{$book->bookId}", ['status' => $newStatus]);
            } catch (Exception $e) {
                Log::error('Failed to update book status via API', [
                    'book_id' => $book->bookId,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('State Pattern [RETURN]: State transition completed', [
                'borrowing_id' => $borrowingId,
                'fine_amount' => $fineAmount,
                'new_state' => $context->getStateName()
            ]);

            return $borrowing->fresh(['user', 'book', 'fines']);
        });
    }

    /**
     * Reserve a book - Using State Pattern and Observer Pattern
     */
    public function reserveBook($userId, $bookId, $expiryDays = null)
    {
        $expiryDays = $expiryDays ?? $this->reservationExpiryDays;

        return DB::transaction(function () use ($userId, $bookId, $expiryDays) {
            // Validate via API calls (cross-module) - Call BookApiController & UserApiController
            $apiUrl = config('app.api_url', config('app.url'));

            $bookResponse = Http::get("{$apiUrl}/api/books/{$bookId}");
            if (!$bookResponse->successful() || !$bookResponse->json()['success']) {
                throw new Exception("Book not found");
            }

            $userResponse = Http::get("{$apiUrl}/api/users/{$userId}");
            if (!$userResponse->successful() || !$userResponse->json()['success']) {
                throw new Exception("User not found");
            }

            // Reconstruct book model from API data for State Pattern
            $bookData = $bookResponse->json()['data'];
            $book = new Book((array)$bookData);
            $book->exists = true;
            $book->bookId = $bookData['bookId'];

            // Get user model
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
    }

    /**
     * Cancel reservation - Using State Pattern
     */
    public function cancelReservation($reservationId)
    {
        return DB::transaction(function () use ($reservationId) {
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
                'user_id' => $reservation->user_id,
                'book_id' => $reservation->book_id
            ]);

            return $reservation;
        });
    }

    /**
     * Renew a borrowing - Using State Pattern
     */
    public function renewBorrowing($borrowingId, $additionalDays = 7)
    {
        return DB::transaction(function () use ($borrowingId, $additionalDays) {
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
    }

    /**
     * Pay fine - Using State Pattern
     */
    public function payFine($fineId, $paymentMethod = 'cash')
    {
        return DB::transaction(function () use ($fineId, $paymentMethod) {
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
                'payment_method' => $paymentMethod,
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
                'payment_method' => $paymentMethod,
                'borrowing_state' => $stateInfo
            ]);

            return $fine->fresh(['borrowing.book', 'user']);
        });
    }

    /**
     * Get borrowing history with state information
     */
    public function getBorrowingHistoryWithStates($userId = null, $isStaff = false)
    {
        // Fetch borrowings with user and fines (same module, direct access OK)
        $query = Borrowing::with(['user', 'fines']);

        if (!$isStaff && $userId) {
            $query->where('user_id', $userId);
        }

        $borrowings = $query->orderBy('borrow_date', 'desc')->get();

        // Fetch book data via API (cross-module: Borrowing → Book Management)
        $apiUrl = config('app.api_url', config('app.url'));

        return $borrowings->map(function ($borrowing) use ($apiUrl) {
            // Call BookApiController to get book data
            $bookData = null;
            try {
                $bookResponse = Http::get("{$apiUrl}/api/books/{$borrowing->book_id}");
                if ($bookResponse->successful() && $bookResponse->json()['success']) {
                    $bookData = $bookResponse->json()['data'];

                    // Reconstruct Book model for State Pattern
                    $book = new Book((array)$bookData);
                    $book->exists = true;
                    $book->bookId = $bookData['bookId'];
                } else {
                    // Fallback if API fails
                    $book = Book::find($borrowing->book_id);
                }
            } catch (Exception $e) {
                // Fallback if API fails
                $book = Book::find($borrowing->book_id);
            }

            // === STATE PATTERN: Get state for each borrowing ===
            $context = new BorrowingContext($book, $borrowing);

            return [
                'id' => $borrowing->id,
                'user' => $borrowing->user,
                'book' => $bookData ?? $book,  // Use API data if available
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
    }

    /**
     * Get fines with state information
     */
    public function getFinesWithStates($userId = null, $isStaff = false)
    {
        // Fetch fines with borrowing and user (same module relationships)
        $query = Fine::with(['borrowing.user', 'user']);

        if (!$isStaff && $userId) {
            $query->where('user_id', $userId);
        }

        $fines = $query->orderBy('created_at', 'desc')->get();

        // Fetch book data via API (cross-module: Borrowing → Book Management)
        $apiUrl = config('app.api_url', config('app.url'));

        return $fines->map(function ($fine) use ($apiUrl) {
            // === STATE PATTERN: Get state context ===
            $stateInfo = 'N/A';
            $bookData = null;

            if ($fine->borrowing && $fine->borrowing->book_id) {
                try {
                    // Call BookApiController to get book data
                    $bookResponse = Http::get("{$apiUrl}/api/books/{$fine->borrowing->book_id}");
                    if ($bookResponse->successful() && $bookResponse->json()['success']) {
                        $bookData = $bookResponse->json()['data'];

                        // Reconstruct Book model for State Pattern
                        $book = new Book((array)$bookData);
                        $book->exists = true;
                        $book->bookId = $bookData['bookId'];

                        $context = new BorrowingContext($book, $fine->borrowing);
                        $stateInfo = $context->getStateName();
                    }
                } catch (Exception $e) {
                    // Fallback if API fails
                    $book = Book::find($fine->borrowing->book_id);
                    if ($book) {
                        $context = new BorrowingContext($book, $fine->borrowing);
                        $stateInfo = $context->getStateName();
                    }
                }
            }

            // Add book data to borrowing object for view access
            $borrowingData = $fine->borrowing;
            if ($borrowingData && $bookData) {
                $borrowingData->book = $bookData;
            }

            return [
                'id' => $fine->id,
                'user' => $fine->user,
                'borrowing' => $borrowingData,
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
    }

    /**
     * Get book availability with state information
     */
    public function getBookAvailabilityWithState($bookId)
    {
        $book = Book::with(['activeBorrowing.user', 'activeReservations.user'])
            ->findOrFail($bookId);

        // === STATE PATTERN: Get current state ===
        $context = new BorrowingContext($book);

        return [
            'book' => $book,
            'current_state' => $context->getStateName(),
            'can_borrow' => $context->canBorrow(),
            'can_return' => $context->canReturn(),
            'can_renew' => $context->canRenew(),
            'is_available' => $context->canBorrow(),
            'current_borrower' => $book->activeBorrowing?->user,
            'due_date' => $book->activeBorrowing?->due_date,
            'reservation_count' => $book->activeReservations->count(),
        ];
    }

    /**
     * Get overdue borrowings with state information
     */
    public function getOverdueBorrowingsWithStates()
    {
        return Borrowing::where('status', 'borrowed')
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


    /**
     * Get user's borrowing history
     */
    public function getUserBorrowingHistory($userId)
    {
        return Borrowing::byUser($userId)->with(['book'])->orderBy('borrow_date', 'desc')->get();
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
        // Fetch fines with borrowing data (same module)
        $fines = Fine::byUser($userId)
            ->with(['borrowing'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch book data via API for each fine (cross-module: Borrowing → Book Management)
        $apiUrl = config('app.api_url', config('app.url'));

        return $fines->map(function ($fine) use ($apiUrl) {
            if ($fine->borrowing && $fine->borrowing->book_id) {
                try {
                    // Call BookApiController to get book data
                    $bookResponse = Http::get("{$apiUrl}/api/books/{$fine->borrowing->book_id}");
                    if ($bookResponse->successful() && $bookResponse->json()['success']) {
                        $fine->borrowing->book = $bookResponse->json()['data'];
                    }
                } catch (Exception $e) {
                    // If API fails, leave book as null or fetch from DB as fallback
                    $fine->borrowing->book = Book::find($fine->borrowing->book_id);
                }
            }
            return $fine;
        });
    }

    /**
     * Get book availability info
     */
    public function getBookAvailability($bookId)
    {
        // Get book data via API (cross-module: Borrowing → Book)
        $apiUrl = config('app.api_url', config('app.url'));
        $bookResponse = Http::get("{$apiUrl}/api/books/{$bookId}");

        if (!$bookResponse->successful() || !$bookResponse->json()['success']) {
            throw new Exception("Book not found");
        }

        $bookData = $bookResponse->json()['data'];
        $book = (object)$bookData;

        // Get borrowing and reservation data from local DB
        $activeBorrowing = Borrowing::where('book_id', $bookId)
            ->where('status', 'borrowed')
            ->with('user')
            ->first();

        $activeReservations = Reservation::where('book_id', $bookId)
            ->where('status', 'active')
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return [
            'book' => $book,
            'is_available' => $book->status === 'Available',
            'is_borrowed' => $book->status === 'Borrowed',
            'current_borrower' => $activeBorrowing?->user,
            'due_date' => $activeBorrowing?->due_date,
            'reservation_count' => $activeReservations->count(),
            'reservation_queue' => $activeReservations,
        ];
    }

    /**
     * Get borrowing history for a specific book
     */
    public function getBookBorrowingHistory($bookId)
    {
        return Borrowing::where('book_id', $bookId)
            ->with(['user'])
            ->orderBy('borrow_date', 'desc')
            ->get();
    }

    /**
     * Get borrowing stats for a specific book
     */
    public function getBookBorrowingStats($bookId)
    {
        $totalBorrows = Borrowing::where('book_id', $bookId)->count();
        $completedBorrows = Borrowing::where('book_id', $bookId)->where('status', 'returned')->count();
        $currentlyBorrowed = Borrowing::where('book_id', $bookId)->where('status', 'borrowed')->count();
        $uniqueBorrowers = Borrowing::where('book_id', $bookId)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'timestamp' => now()->toIso8601String(),
            'total_borrows' => $totalBorrows,
            'completed_borrows' => $completedBorrows,
            'currently_borrowed' => $currentlyBorrowed,
            'unique_borrowers' => $uniqueBorrowers,
            'history' => $this->getBookBorrowingHistory($bookId),
        ];
    }
}
