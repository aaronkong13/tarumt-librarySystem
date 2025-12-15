<?php

namespace App\Observers\Reservation;

use App\Contracts\ReservationObserverInterface;
use Illuminate\Support\Facades\Log;

class LoggingObserver implements ReservationObserverInterface
{
    /**
     * Log when reserved book becomes available
     */
    public function onBookAvailable($book, $reservation): void
    {
        Log::channel('daily')->info('RESERVATION EVENT: Book became available', [
            'observer' => 'LoggingObserver',
            'event_type' => 'BOOK_AVAILABLE',
            'timestamp' => now()->toISOString(),
            'book_id' => $book->bookId,
            'book_title' => $book->title,
            'reservation_id' => $reservation->id,
            'reserved_by_user_id' => $reservation->user_id,
            'reserved_by_user_name' => $reservation->user->name,
            'reservation_date' => $reservation->reservation_date->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log when reservation is about to expire
     */
    public function onReservationExpiring($reservation): void
    {
        Log::channel('daily')->warning('RESERVATION EVENT: Reservation expiring soon', [
            'observer' => 'LoggingObserver',
            'event_type' => 'RESERVATION_EXPIRING',
            'timestamp' => now()->toISOString(),
            'reservation_id' => $reservation->id,
            'book_title' => $reservation->book->title,
            'user_id' => $reservation->user_id,
            'expiry_date' => $reservation->expiry_date->format('Y-m-d H:i:s'),
            'hours_remaining' => now()->diffInHours($reservation->expiry_date),
        ]);
    }

    /**
     * Log when reservation has expired
     */
    public function onReservationExpired($reservation): void
    {
        Log::channel('daily')->info('RESERVATION EVENT: Reservation expired', [
            'observer' => 'LoggingObserver',
            'event_type' => 'RESERVATION_EXPIRED',
            'timestamp' => now()->toISOString(),
            'reservation_id' => $reservation->id,
            'book_title' => $reservation->book->title,
            'user_id' => $reservation->user_id,
            'original_expiry_date' => $reservation->expiry_date->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Log when reservation is fulfilled
     */
    public function onReservationFulfilled($reservation): void
    {
        Log::channel('daily')->info('RESERVATION EVENT: Reservation fulfilled', [
            'observer' => 'LoggingObserver',
            'event_type' => 'RESERVATION_FULFILLED',
            'timestamp' => now()->toISOString(),
            'reservation_id' => $reservation->id,
            'book_title' => $reservation->book->title,
            'user_id' => $reservation->user_id,
            'fulfilled_at' => now()->toISOString(),
        ]);
    }
}
