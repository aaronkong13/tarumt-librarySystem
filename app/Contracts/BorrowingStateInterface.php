<?php

namespace App\Contracts;

interface BorrowingStateInterface
{
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
}
