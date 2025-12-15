<?php

namespace App\States\Borrowing;

use App\Contracts\BorrowingStateInterface;
use App\Models\Book;
use App\Models\Borrowing;

class BorrowingContext
{
    protected BorrowingStateInterface $state;
    protected Book $book;
    protected ?Borrowing $borrowing = null;

    public function __construct(Book $book, ?Borrowing $borrowing = null)
    {
        $this->book = $book;
        $this->borrowing = $borrowing;
        $this->initializeState();
    }

    /**
     * Initialize state based on current book/borrowing status
     */
    protected function initializeState(): void
    {
        if ($this->borrowing) {
            // Check borrowing status
            if ($this->borrowing->status === 'returned') {
                $this->state = new ReturnedState();
            } elseif ($this->borrowing->status === 'borrowed') {
                // Check if overdue
                if ($this->borrowing->due_date->isPast()) {
                    $this->state = new OverdueState();
                } else {
                    $this->state = new BorrowedState();
                }
            } else {
                $this->state = new AvailableState();
            }
        } else {
            // No borrowing - check if book is borrowed
            $activeBorrowing = $this->book->borrowings()
                ->where('status', 'borrowed')
                ->first();

            if ($activeBorrowing) {
                $this->borrowing = $activeBorrowing;
                if ($activeBorrowing->due_date->isPast()) {
                    $this->state = new OverdueState();
                } else {
                    $this->state = new BorrowedState();
                }
            } else {
                $this->state = new AvailableState();
            }
        }
    }

    public function setState(BorrowingStateInterface $state): void
    {
        $this->state = $state;
    }

    public function getState(): BorrowingStateInterface
    {
        return $this->state;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getBorrowing(): ?Borrowing
    {
        return $this->borrowing;
    }

    public function setBorrowing(Borrowing $borrowing): void
    {
        $this->borrowing = $borrowing;
    }

    /**
     * Delegate actions to current state
     */
    public function borrow(): void
    {
        $this->state->borrow($this);
    }

    public function returnBook(): void
    {
        $this->state->returnBook($this);
    }

    public function renew(): void
    {
        $this->state->renew($this);
    }

    public function canBorrow(): bool
    {
        return $this->state->canBorrow();
    }

    public function canReturn(): bool
    {
        return $this->state->canReturn();
    }

    public function canRenew(): bool
    {
        return $this->state->canRenew();
    }

    public function getStateName(): string
    {
        return $this->state->getStateName();
    }
}
