<?php

namespace App\States\Borrowing;

use App\Contracts\BorrowingStateInterface;
use App\Models\Fine;
use Exception;

class OverdueState implements BorrowingStateInterface
{
    protected float $fineRatePerDay = 0.50;

    public function borrow($context): void
    {
        throw new Exception('Book is currently overdue. Please return it first.');
    }

    public function returnBook($context): void
    {
        $borrowing = $context->getBorrowing();

        if (!$borrowing) {
            throw new Exception('No active borrowing found.');
        }

        // Calculate fine
        $daysOverdue = now()->diffInDays($borrowing->due_date);
        $fineAmount = $daysOverdue * $this->fineRatePerDay;

        // Create fine record
        if ($fineAmount > 0) {
            Fine::create([
                'user_id' => $borrowing->user_id,
                'borrowing_id' => $borrowing->id,
                'amount' => $fineAmount,
                'reason' => "Overdue by {$daysOverdue} days",
                'status' => 'unpaid',
            ]);
        }

        // Update borrowing with fine
        $borrowing->update([
            'fine_amount' => $fineAmount,
        ]);

        // Transition to returned state
        $context->setState(new ReturnedState());
        $context->getBook()->update(['status' => 'Available']);
    }

    public function renew($context): void
    {
        throw new Exception('Cannot renew overdue book. Please return and pay fines first.');
    }

    public function canBorrow(): bool
    {
        return false;
    }

    public function canReturn(): bool
    {
        return true;
    }

    public function canRenew(): bool
    {
        return false;
    }

    public function getStateName(): string
    {
        return 'overdue';
    }

    // =====================================================================
    // FINE METHODS (Not applicable for Borrowing states)
    // =====================================================================

    public function pay($context): void
    {
        throw new Exception('Pay operation not applicable for Borrowing states.');
    }

    public function waive($context): void
    {
        throw new Exception('Waive operation not applicable for Borrowing states.');
    }

    public function updateAmount($context, float $amount): void
    {
        throw new Exception('Update amount operation not applicable for Borrowing states.');
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

    public function getStateDescription(): string
    {
        return 'Book is overdue. Fine will be calculated on return.';
    }
}
