<?php

namespace App\States\Borrowing;

use App\Contracts\BorrowingStateInterface;
use Exception;

class BorrowedState implements BorrowingStateInterface
{
    public function borrow($context): void
    {
        throw new Exception('Book is already borrowed.');
    }

    public function returnBook($context): void
    {
        // Calculate fine if overdue
        $borrowing = $context->getBorrowing();
        $fineAmount = 0;

        if ($borrowing && $borrowing->due_date->isPast()) {
            // Transition to overdue first, then return
            $context->setState(new OverdueState());
            $context->getState()->returnBook($context);
            return;
        }

        // No fine - direct return
        $context->setState(new ReturnedState());
        $context->getBook()->update(['status' => 'Available']);
    }

    public function renew($context): void
    {
        $borrowing = $context->getBorrowing();

        if (!$borrowing) {
            throw new Exception('No active borrowing found.');
        }

        // Check if there are reservations
        if ($context->getBook()->hasActiveReservation()) {
            throw new Exception('Cannot renew - book has pending reservations.');
        }

        // Extend due date by 7 days
        $borrowing->update([
            'due_date' => $borrowing->due_date->addDays(7)
        ]);
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
        return true;
    }

    public function getStateName(): string
    {
        return 'borrowed';
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
        return 'Book is currently borrowed.';
    }
}
