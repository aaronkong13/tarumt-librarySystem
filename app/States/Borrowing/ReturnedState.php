<?php

namespace App\States\Borrowing;

use App\Contracts\BorrowingStateInterface;
use Exception;

class ReturnedState implements BorrowingStateInterface
{
    public function borrow($context): void
    {
        // Book can be borrowed again - transition to BorrowedState
        $context->setState(new BorrowedState());
        $context->getBook()->update(['status' => 'Borrowed']);
    }

    public function returnBook($context): void
    {
        throw new Exception('Book has already been returned.');
    }

    public function renew($context): void
    {
        throw new Exception('Cannot renew a returned book.');
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
        return 'returned';
    }
}
