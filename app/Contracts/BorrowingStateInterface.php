<?php

namespace App\Contracts;

/**
 * BorrowingStateInterface - State Pattern Interface for Borrowing & Fine Module
 *
 * Defines the contract for all borrowing and fine states.
 * Each state implements allowed actions and transitions.
 *
 * Integrated: FineStateInterface methods for unified state management
 */
interface BorrowingStateInterface
{
    // =====================================================================
    // BORROWING STATE METHODS
    // =====================================================================

    /**
     * Handle borrowing action
     */
    public function borrow($context): void;

    /**
     * Handle return action
     */
    public function returnBook($context): void;

    /**
     * Handle renew action
     */
    public function renew($context): void;

    /**
     * Check if book can be borrowed
     */
    public function canBorrow(): bool;

    /**
     * Check if book can be returned
     */
    public function canReturn(): bool;

    /**
     * Check if book can be renewed
     */
    public function canRenew(): bool;

    /**
     * Get state name
     */
    public function getStateName(): string;

    // =====================================================================
    // FINE STATE METHODS (Integrated from FineStateInterface)
    // =====================================================================

    /**
     * Handle pay action for fines
     */
    public function pay($context): void;

    /**
     * Handle waive action for fines
     */
    public function waive($context): void;

    /**
     * Handle update amount action for fines
     */
    public function updateAmount($context, float $amount): void;

    /**
     * Check if fine can be paid
     */
    public function canPay(): bool;

    /**
     * Check if fine can be waived
     */
    public function canWaive(): bool;

    /**
     * Check if fine amount can be updated
     */
    public function canUpdateAmount(): bool;

    /**
     * Get state description
     */
    public function getStateDescription(): string;
}
