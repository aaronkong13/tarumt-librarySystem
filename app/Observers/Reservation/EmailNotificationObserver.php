<?php

namespace App\Observers\Reservation;

use App\Contracts\ReservationObserverInterface;
use Illuminate\Support\Facades\Log;

class EmailNotificationObserver implements ReservationObserverInterface
{
    /**
     * Notify user when reserved book becomes available
     */
    public function onBookAvailable($book, $reservation): void
    {
        $user = $reservation->user;

        Log::info('Reservation Notification: Book Available', [
            'observer' => 'EmailNotificationObserver',
            'event' => 'book_available',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'book_id' => $book->bookId,
            'book_title' => $book->title,
            'reservation_id' => $reservation->id,
            'message' => "Dear {$user->name}, the book '{$book->title}' you reserved is now available. Please collect it within 3 days."
        ]);

        // In production, send actual email:
        // Mail::to($user->email)->send(new BookAvailableMail($book, $reservation));
    }

    /**
     * Notify user when reservation is about to expire
     */
    public function onReservationExpiring($reservation): void
    {
        $user = $reservation->user;
        $book = $reservation->book;

        Log::info('Reservation Notification: Expiring Soon', [
            'observer' => 'EmailNotificationObserver',
            'event' => 'reservation_expiring',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'book_title' => $book->title,
            'expiry_date' => $reservation->expiry_date->format('Y-m-d'),
            'message' => "Dear {$user->name}, your reservation for '{$book->title}' will expire on {$reservation->expiry_date->format('M d, Y')}."
        ]);
    }

    /**
     * Notify user when reservation has expired
     */
    public function onReservationExpired($reservation): void
    {
        $user = $reservation->user;
        $book = $reservation->book;

        Log::info('Reservation Notification: Expired', [
            'observer' => 'EmailNotificationObserver',
            'event' => 'reservation_expired',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'book_title' => $book->title,
            'message' => "Dear {$user->name}, your reservation for '{$book->title}' has expired."
        ]);
    }

    /**
     * Notify user when reservation is fulfilled
     */
    public function onReservationFulfilled($reservation): void
    {
        $user = $reservation->user;
        $book = $reservation->book;

        Log::info('Reservation Notification: Fulfilled', [
            'observer' => 'EmailNotificationObserver',
            'event' => 'reservation_fulfilled',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'book_title' => $book->title,
            'message' => "Dear {$user->name}, your reservation for '{$book->title}' has been fulfilled. Enjoy your book!"
        ]);
    }
}
