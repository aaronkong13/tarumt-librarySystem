<?php

namespace App\States\Fine;

use App\Contracts\BorrowingStateInterface;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * UnpaidState - State for unpaid fines
 *
 * This is the initial state for all newly created fines.
 * Allows: pay, waive, updateAmount
 * Transitions to: PaidState (on pay), WaivedState (on waive)
 */
class UnpaidState implements BorrowingStateInterface
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
     * Pay the fine - transitions to PaidState
     */
    public function pay($context): void
    {
        $fine = $context->getFine();

        Log::info('Fine State Pattern [PAY]: Processing payment', [
            'fine_id' => $fine->id,
            'amount' => $fine->amount,
            'current_state' => $this->getStateName(),
        ]);

        // Transition to PaidState
        $context->setState(new PaidState());
    }

    /**
     * Waive the fine - transitions to WaivedState
     */
    public function waive($context): void
    {
        $fine = $context->getFine();

        Log::info('Fine State Pattern [WAIVE]: Processing waiver', [
            'fine_id' => $fine->id,
            'amount' => $fine->amount,
            'current_state' => $this->getStateName(),
        ]);

        // Transition to WaivedState
        $context->setState(new WaivedState());
    }

    /**
     * Update fine amount (only allowed in unpaid state)
     */
    public function updateAmount($context, float $amount): void
    {
        $fine = $context->getFine();
        $oldAmount = $fine->amount;

        Log::info('Fine State Pattern [UPDATE]: Amount updated', [
            'fine_id' => $fine->id,
            'old_amount' => $oldAmount,
            'new_amount' => $amount,
            'current_state' => $this->getStateName(),
        ]);
    }

    public function canPay(): bool
    {
        return true;
    }

    public function canWaive(): bool
    {
        return true;
    }

    public function canUpdateAmount(): bool
    {
        return true;
    }

    public function getStateName(): string
    {
        return 'unpaid';
    }

    public function getStateDescription(): string
    {
        return 'Fine is pending payment. User must pay before borrowing privileges are restored.';
    }
}
