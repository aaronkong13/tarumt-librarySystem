<?php

namespace App\States\Fine;

use App\Contracts\BorrowingStateInterface;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * WaivedState - State for waived fines
 *
 * Final state after fine is waived by staff.
 * No further transitions allowed.
 * Denies: pay, waive, updateAmount
 */
class WaivedState implements BorrowingStateInterface
{
    // =====================================================================
    // BORROWING METHODS (Not applicable for Fine states)
    // =====================================================================

    public function borrow($context): void
    {
        throw new Exception('Borrow operation not applicable for Fine states.');
    }

    public function returnBook($context): void
    {
        throw new Exception('Return operation not applicable for Fine states.');
    }

    public function renew($context): void
    {
        throw new Exception('Renew operation not applicable for Fine states.');
    }

    public function canBorrow(): bool
    {
        return false;
    }

    public function canReturn(): bool
    {
        return false;
    }

    public function canRenew(): bool
    {
        return false;
    }

    // =====================================================================
    // FINE METHODS
    // =====================================================================

    /**
     * Cannot pay a waived fine
     */
    public function pay($context): void
    {
        $fine = $context->getFine();

        Log::warning('Fine State Pattern [PAY]: Attempted to pay waived fine', [
            'fine_id' => $fine->id,
            'current_state' => $this->getStateName(),
        ]);

        throw new Exception('Cannot pay a waived fine.');
    }

    /**
     * Cannot waive an already waived fine
     */
    public function waive($context): void
    {
        $fine = $context->getFine();

        Log::warning('Fine State Pattern [WAIVE]: Attempted to waive already waived fine', [
            'fine_id' => $fine->id,
            'current_state' => $this->getStateName(),
        ]);

        throw new Exception('This fine has already been waived.');
    }

    /**
     * Cannot update amount of waived fine
     */
    public function updateAmount($context, float $amount): void
    {
        $fine = $context->getFine();

        Log::warning('Fine State Pattern [UPDATE]: Attempted to update waived fine', [
            'fine_id' => $fine->id,
            'current_state' => $this->getStateName(),
        ]);

        throw new Exception('Cannot update amount of a waived fine.');
    }

    public function canPay(): bool
    {
        return false;
    }

    public function canWaive(): bool
    {
        return false;
    }

    public function canUpdateAmount(): bool
    {
        return false;
    }

    public function getStateName(): string
    {
        return 'waived';
    }

    public function getStateDescription(): string
    {
        return 'Fine has been waived by staff. User borrowing privileges are restored.';
    }
}
