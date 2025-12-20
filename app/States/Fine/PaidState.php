<?php

namespace App\States\Fine;

use App\Contracts\BorrowingStateInterface;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * PaidState - State for paid fines
 *
 * Final state after payment is processed.
 * No further transitions allowed.
 * Denies: pay, waive, updateAmount
 */
class PaidState implements BorrowingStateInterface
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
     * Cannot pay an already paid fine
     */
    public function pay($context): void
    {
        $fine = $context->getFine();

        Log::warning('Fine State Pattern [PAY]: Attempted to pay already paid fine', [
            'fine_id' => $fine->id,
            'current_state' => $this->getStateName(),
        ]);

        throw new Exception('This fine has already been paid.');
    }

    /**
     * Cannot waive a paid fine
     */
    public function waive($context): void
    {
        $fine = $context->getFine();

        Log::warning('Fine State Pattern [WAIVE]: Attempted to waive paid fine', [
            'fine_id' => $fine->id,
            'current_state' => $this->getStateName(),
        ]);

        throw new Exception('Cannot waive a paid fine.');
    }

    /**
     * Cannot update amount of paid fine
     */
    public function updateAmount($context, float $amount): void
    {
        $fine = $context->getFine();

        Log::warning('Fine State Pattern [UPDATE]: Attempted to update paid fine', [
            'fine_id' => $fine->id,
            'current_state' => $this->getStateName(),
        ]);

        throw new Exception('Cannot update amount of a paid fine.');
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
        return 'paid';
    }

    public function getStateDescription(): string
    {
        return 'Fine has been paid. User borrowing privileges are restored.';
    }
}
