<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;
use App\Observers\Reservation\ReservationSubject;
use App\Observers\Reservation\EmailNotificationObserver;
use App\Observers\Reservation\LoggingObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ReservationService - Handles Book Reservation Logic
 * 
 * Implements Observer Pattern for automatic notifications
 * Uses parameterized queries for security (Laravel Eloquent ORM)
 */
class ReservationService
{
    protected int $reservationExpiryDays = 3; // 3 days to collect book
    protected int $maxActiveReservations = 5; // Max active reservations per student

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
     * Reserve a book (Student Only)
     * 
     * Business Rules:
     * - Book must be borrowed (not available)
     * - Student cannot have duplicate reservations
     * - Student cannot exceed max active reservations
     * - Uses FIFO queue system
     * 
     * Security: Uses Eloquent ORM parameterized queries
     */
    public function reserveBook(int $userId, int $bookId): Reservation
    {
        return DB::transaction(function () use ($userId, $bookId) {
            // Input validation (whitelist - only integers)
            if (!is_numeric($userId) || !is_numeric($bookId)) {
                throw new Exception('Invalid user ID or book ID format.');
            }

            // Fetch with parameterized query (Laravel ORM)
            $book = Book::findOrFail($bookId);
            $user = User::findOrFail($userId);

            // Validate user role
            if (!$user->isStudent()) {
                throw new Exception('Only students can reserve books.');
            }

            // Check if book is available (cannot reserve available books)
            if ($book->status === 'Available') {
                throw new Exception('This book is currently available. Please borrow it directly.');
            }

            // Check if book is borrowed
            if ($book->status !== 'Borrowed') {
                throw new Exception('This book cannot be reserved at the moment.');
            }

            // Check for duplicate reservation
            $existingReservation = Reservation::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->whereIn('status', [
                    Reservation::STATUS_WAITING, 
                    Reservation::STATUS_NOTIFIED,
                    'active'
                ])
                ->first();

            if ($existingReservation) {
                throw new Exception('You have already reserved this book.');
            }

            // Check max active reservations limit
            $activeCount = Reservation::where('user_id', $userId)
                ->whereIn('status', [
                    Reservation::STATUS_WAITING, 
                    Reservation::STATUS_NOTIFIED,
                    'active'
                ])
                ->count();

            if ($activeCount >= $this->maxActiveReservations) {
                throw new Exception("You have reached the maximum reservation limit ({$this->maxActiveReservations}).");
            }

            // Calculate next queue position (FIFO)
            $maxPosition = Reservation::where('book_id', $bookId)
                ->whereIn('status', [
                    Reservation::STATUS_WAITING, 
                    Reservation::STATUS_NOTIFIED,
                    'active'
                ])
                ->max('queue_position') ?? 0;

            $nextPosition = $maxPosition + 1;

            // Create reservation record (parameterized via Eloquent)
            $reservation = Reservation::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'reservation_date' => now()->toDateString(),
                'expiry_date' => null, // Set when notified
                'status' => Reservation::STATUS_WAITING,
                'queue_position' => $nextPosition,
                'notified_at' => null,
            ]);

            Log::info('Reservation created', [
                'reservation_id' => $reservation->id,
                'user_id' => $userId,
                'book_id' => $bookId,
                'queue_position' => $nextPosition,
            ]);

            return $reservation->load(['user', 'book']);
        });
    }

    /**
     * Cancel reservation (Student Only - Own reservations)
     * 
     * Business Rules:
     * - Only reservation owner can cancel
     * - Queue is automatically re-ordered
     * 
     * Security: Authorization check enforced
     */
    public function cancelReservation(int $reservationId, int $userId): bool
    {
        return DB::transaction(function () use ($reservationId, $userId) {
            // Find reservation with authorization check
            $reservation = Reservation::where('id', $reservationId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Cannot cancel fulfilled/expired reservations
            if (in_array($reservation->status, [
                Reservation::STATUS_FULFILLED, 
                Reservation::STATUS_EXPIRED
            ])) {
                throw new Exception('Cannot cancel this reservation.');
            }

            $bookId = $reservation->book_id;
            $cancelledPosition = $reservation->queue_position;

            // Delete reservation
            $reservation->delete();

            // Re-order queue (decrement positions after cancelled one)
            Reservation::where('book_id', $bookId)
                ->where('queue_position', '>', $cancelledPosition)
                ->whereIn('status', [
                    Reservation::STATUS_WAITING, 
                    Reservation::STATUS_NOTIFIED,
                    'active'
                ])
                ->decrement('queue_position');

            Log::info('Reservation cancelled and queue re-ordered', [
                'reservation_id' => $reservationId,
                'user_id' => $userId,
                'book_id' => $bookId,
            ]);

            return true;
        });
    }

    /**
     * Get reservation queue for a book (Staff view)
     * 
     * Returns all active reservations ordered by queue position
     */
    public function getReservationQueue(int $bookId): array
    {
        $reservations = Reservation::where('book_id', $bookId)
            ->whereIn('status', [
                Reservation::STATUS_WAITING, 
                Reservation::STATUS_NOTIFIED,
                'active'
            ])
            ->with(['user', 'book'])
            ->orderBy('queue_position')
            ->orderBy('created_at')
            ->get();

        return $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'user_name' => $reservation->user->name,
                'user_email' => $reservation->user->email,
                'queue_position' => $reservation->queue_position,
                'status' => $reservation->status,
                'status_label' => $reservation->getStatusLabel(),
                'reservation_date' => $reservation->reservation_date->format('Y-m-d H:i:s'),
                'notified_at' => $reservation->notified_at?->format('Y-m-d H:i:s'),
                'expiry_date' => $reservation->expiry_date?->format('Y-m-d H:i:s'),
                'remaining_days' => $reservation->getRemainingDays(),
            ];
        })->toArray();
    }

    /**
     * Get user's reservations (Student view)
     */
    public function getUserReservations(int $userId): array
    {
        $reservations = Reservation::where('user_id', $userId)
            ->whereIn('status', [
                Reservation::STATUS_WAITING, 
                Reservation::STATUS_NOTIFIED,
                'active'
            ])
            ->with('book')
            ->orderBy('created_at', 'desc')
            ->get();

        return $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'book_id' => $reservation->book_id,
                'book_title' => $reservation->book->title,
                'book_author' => $reservation->book->author,
                'book_isbn' => $reservation->book->isbn,
                'queue_position' => $reservation->queue_position,
                'status' => $reservation->status,
                'status_label' => $reservation->getStatusLabel(),
                'reservation_date' => $reservation->reservation_date->format('Y-m-d'),
                'notified_at' => $reservation->notified_at?->format('Y-m-d H:i'),
                'expiry_date' => $reservation->expiry_date?->format('Y-m-d'),
                'remaining_days' => $reservation->getRemainingDays(),
                'can_cancel' => !in_array($reservation->status, [
                    Reservation::STATUS_FULFILLED, 
                    Reservation::STATUS_EXPIRED
                ]),
            ];
        })->toArray();
    }

    /**
     * Notify next person in queue when book becomes available
     * 
     * Called by Observer Pattern when book is returned
     * Implements automatic notification system
     */
    public function notifyNextInQueue(Book $book): ?Reservation
    {
        return DB::transaction(function () use ($book) {
            // Get first person in queue
            $nextReservation = Reservation::where('book_id', $book->bookId)
                ->where('status', Reservation::STATUS_WAITING)
                ->orderBy('queue_position')
                ->orderBy('created_at')
                ->with('user')
                ->first();

            if (!$nextReservation) {
                Log::info('No reservations in queue', ['book_id' => $book->bookId]);
                return null;
            }

            // Update reservation status to notified
            $nextReservation->update([
                'status' => Reservation::STATUS_NOTIFIED,
                'notified_at' => now(),
                'expiry_date' => now()->addDays($this->reservationExpiryDays),
            ]);

            // Observer Pattern: Notify observers
            $subject = $this->createReservationSubject($book);
            $subject->notifyBookAvailable();

            Log::info('Next user notified about book availability', [
                'reservation_id' => $nextReservation->id,
                'user_id' => $nextReservation->user_id,
                'book_id' => $book->bookId,
                'expiry_date' => $nextReservation->expiry_date,
            ]);

            return $nextReservation;
        });
    }

    /**
     * Check and expire old notifications (Scheduled Task)
     * 
     * Called by scheduled command every hour
     * Automatically moves to next person if notification expired
     */
    public function checkAndExpireReservations(): array
    {
        $expiredCount = 0;
        $notifiedCount = 0;

        return DB::transaction(function () use (&$expiredCount, &$notifiedCount) {
            // Find expired notifications
            $expiredReservations = Reservation::where('status', Reservation::STATUS_NOTIFIED)
                ->where('expiry_date', '<', now())
                ->with(['user', 'book'])
                ->get();

            foreach ($expiredReservations as $reservation) {
                // Mark as expired
                $reservation->update(['status' => Reservation::STATUS_EXPIRED]);
                $expiredCount++;

                Log::info('Reservation expired', [
                    'reservation_id' => $reservation->id,
                    'user_id' => $reservation->user_id,
                    'book_id' => $reservation->book_id,
                ]);

                // Observer Pattern: Notify about expiration
                $subject = $this->createReservationSubject($reservation->book);
                $subject->notifyReservationExpired();

                // Check if book is still available, notify next person
                $book = Book::find($reservation->book_id);
                if ($book && $book->status === 'Available') {
                    $nextReservation = $this->notifyNextInQueue($book);
                    if ($nextReservation) {
                        $notifiedCount++;
                    }
                }
            }

            return [
                'expired_count' => $expiredCount,
                'notified_count' => $notifiedCount,
            ];
        });
    }

    /**
     * Mark reservation as fulfilled when book is borrowed
     * 
     * Called during borrow process
     */
    public function fulfillReservation(int $userId, int $bookId): bool
    {
        $reservation = Reservation::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->whereIn('status', [
                Reservation::STATUS_NOTIFIED,
                Reservation::STATUS_WAITING,
                'active'
            ])
            ->first();

        if ($reservation) {
            $reservation->update(['status' => Reservation::STATUS_FULFILLED]);
            
            Log::info('Reservation fulfilled', [
                'reservation_id' => $reservation->id,
                'user_id' => $userId,
                'book_id' => $bookId,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get notified reservations for user (for notification page)
     */
    public function getUserNotifications(int $userId): array
    {
        $notifications = Reservation::where('user_id', $userId)
            ->where('status', Reservation::STATUS_NOTIFIED)
            ->with('book')
            ->orderBy('notified_at', 'desc')
            ->get();

        return $notifications->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'book_id' => $reservation->book_id,
                'book_title' => $reservation->book->title,
                'book_author' => $reservation->book->author,
                'book_cover' => $reservation->book->cover_image,
                'notified_at' => $reservation->notified_at->format('M d, Y h:i A'),
                'expiry_date' => $reservation->expiry_date->format('M d, Y'),
                'remaining_days' => $reservation->getRemainingDays(),
                'is_expired' => $reservation->isExpired(),
            ];
        })->toArray();
    }
}
