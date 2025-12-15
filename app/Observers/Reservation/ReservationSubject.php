<?php

namespace App\Observers\Reservation;

use App\Contracts\ReservationObserverInterface;
use App\Contracts\ReservationSubjectInterface;
use App\Models\Book;
use App\Models\Reservation;

class ReservationSubject implements ReservationSubjectInterface
{
    /**
     * List of attached observers
     */
    protected array $observers = [];

    /**
     * The book being observed
     */
    protected Book $book;

    public function __construct(Book $book)
    {
        $this->book = $book;
    }

    /**
     * Attach an observer
     */
    public function attach(ReservationObserverInterface $observer): void
    {
        $key = spl_object_hash($observer);
        $this->observers[$key] = $observer;
    }

    /**
     * Detach an observer
     */
    public function detach(ReservationObserverInterface $observer): void
    {
        $key = spl_object_hash($observer);
        unset($this->observers[$key]);
    }

    /**
     * Get all active reservations for this book
     */
    protected function getActiveReservations()
    {
        return Reservation::where('book_id', $this->book->bookId)
            ->where('status', 'active')
            ->orderBy('queue_position')
            ->with('user')
            ->get();
    }

    /**
     * Notify all observers that the book is now available
     */
    public function notifyBookAvailable(): void
    {
        $reservations = $this->getActiveReservations();

        // Notify the first person in queue (highest priority)
        $nextReservation = $reservations->first();

        if ($nextReservation) {
            foreach ($this->observers as $observer) {
                $observer->onBookAvailable($this->book, $nextReservation);
            }
        }
    }

    /**
     * Notify observers about reservations expiring soon (within 24 hours)
     */
    public function notifyReservationExpiring(): void
    {
        $reservations = $this->getActiveReservations()
            ->filter(function ($reservation) {
                $hoursUntilExpiry = now()->diffInHours($reservation->expiry_date, false);
                return $hoursUntilExpiry > 0 && $hoursUntilExpiry <= 24;
            });

        foreach ($reservations as $reservation) {
            foreach ($this->observers as $observer) {
                $observer->onReservationExpiring($reservation);
            }
        }
    }

    /**
     * Notify observers about expired reservations and update status
     */
    public function notifyReservationExpired(): void
    {
        $expiredReservations = Reservation::where('book_id', $this->book->bookId)
            ->where('status', 'active')
            ->where('expiry_date', '<', now())
            ->with('user', 'book')
            ->get();

        foreach ($expiredReservations as $reservation) {
            // Update status
            $reservation->update(['status' => 'expired']);

            // Notify observers
            foreach ($this->observers as $observer) {
                $observer->onReservationExpired($reservation);
            }
        }

        // Reorder queue positions after expiry
        $this->reorderQueuePositions();
    }

    /**
     * Notify when a reservation is fulfilled
     */
    public function notifyReservationFulfilled(Reservation $reservation): void
    {
        foreach ($this->observers as $observer) {
            $observer->onReservationFulfilled($reservation);
        }
    }

    /**
     * Reorder queue positions after a reservation is removed
     */
    protected function reorderQueuePositions(): void
    {
        $activeReservations = Reservation::where('book_id', $this->book->bookId)
            ->where('status', 'active')
            ->orderBy('reservation_date')
            ->get();

        foreach ($activeReservations as $index => $reservation) {
            $reservation->update(['queue_position' => $index + 1]);
        }
    }

    /**
     * Get the book
     */
    public function getBook(): Book
    {
        return $this->book;
    }
}
