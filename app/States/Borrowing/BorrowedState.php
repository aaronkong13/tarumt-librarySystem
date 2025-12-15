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
}
