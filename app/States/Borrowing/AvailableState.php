<?php

namespace App\States\Borrowing;

use App\Contracts\BorrowingStateInterface;
use Exception;

class AvailableState implements BorrowingStateInterface
{
    public function borrow($context): void
    {
        // Book can be borrowed - transition to BorrowedState
        $context->setState(new BorrowedState());
        $context->getBook()->update(['status' => 'Borrowed']);
    }

    public function returnBook($context): void
    {
        throw new Exception('Book is not currently borrowed.');
    }

    public function renew($context): void
    {
        throw new Exception('Book is not currently borrowed.');
    }

    public function canBorrow(): bool
    {
        return true;
    }

    public function canReturn(): bool
    {
        return false;
    }

    public function canRenew(): bool
    {
        return false;
    }

    public function getStateName(): string
    {
        return 'available';
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
        return 'Book is available for borrowing.';
    }
}
