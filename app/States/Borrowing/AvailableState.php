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
}
