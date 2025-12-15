<?php

namespace App\Contracts;

interface ReservationObserverInterface
{
    /**
     * Called when a reserved book becomes available
     */
    public function onBookAvailable($book, $reservation): void;

    /**
     * Called when reservation is about to expire
     */
    public function onReservationExpiring($reservation): void;

    /**
     * Called when reservation has expired
     */
    public function onReservationExpired($reservation): void;

    /**
     * Called when reservation is fulfilled (book borrowed)
     */
    public function onReservationFulfilled($reservation): void;
}
