<?php

namespace App\Contracts;

interface ReservationSubjectInterface
{
    /**
     * Attach an observer
     */
    public function attach(ReservationObserverInterface $observer): void;

    /**
     * Detach an observer
     */
    public function detach(ReservationObserverInterface $observer): void;

    /**
     * Notify all observers about book availability
     */
    public function notifyBookAvailable(): void;

    /**
     * Notify observers about expiring reservations
     */
    public function notifyReservationExpiring(): void;

    /**
     * Notify observers about expired reservations
     */
    public function notifyReservationExpired(): void;
}
