<?php

namespace App\States\Fine;

use App\Contracts\BorrowingStateInterface;
use App\Models\Fine;
use Illuminate\Support\Facades\Log;

/**
 * FineContext - State Pattern Context for Fine Module
 *
 * Manages fine state transitions and delegates operations to current state.
 * Integrates with BorrowingContext for borrowing state awareness.
 */
class FineContext
{
    protected BorrowingStateInterface $state;
    protected Fine $fine;

    public function __construct(Fine $fine)
    {
        $this->fine = $fine;
        $this->initializeState();
    }

    /**
     * Initialize state based on current fine status
     */
    protected function initializeState(): void
    {
        switch ($this->fine->status) {
            case Fine::STATUS_PAID:
                $this->state = new PaidState();
                break;
            case Fine::STATUS_WAIVED:
                $this->state = new WaivedState();
                break;
            case Fine::STATUS_UNPAID:
            default:
                $this->state = new UnpaidState();
                break;
        }

        Log::debug('Fine State Pattern: Initialized', [
            'fine_id' => $this->fine->id,
            'status' => $this->fine->status,
            'state' => $this->state->getStateName(),
        ]);
    }

    /**
     * Set current state
     */
    public function setState(BorrowingStateInterface $state): void
    {
        $previousState = $this->state->getStateName();
        $this->state = $state;

        Log::info('Fine State Pattern: State transition', [
            'fine_id' => $this->fine->id,
            'from_state' => $previousState,
            'to_state' => $state->getStateName(),
        ]);
    }

    /**
     * Get current state
     */
    public function getState(): BorrowingStateInterface
    {
        return $this->state;
    }

    /**
     * Get fine model
     */
    public function getFine(): Fine
    {
        return $this->fine;
    }

    /**
     * Delegate pay action to current state
     */
    public function pay(): void
    {
        $this->state->pay($this);
    }

    /**
     * Delegate waive action to current state
     */
    public function waive(): void
    {
        $this->state->waive($this);
    }

    /**
     * Delegate update amount action to current state
     */
    public function updateAmount(float $amount): void
    {
        $this->state->updateAmount($this, $amount);
    }

    /**
     * Check if fine can be paid
     */
    public function canPay(): bool
    {
        return $this->state->canPay();
    }

    /**
     * Check if fine can be waived
     */
    public function canWaive(): bool
    {
        return $this->state->canWaive();
    }

    /**
     * Check if fine amount can be updated
     */
    public function canUpdateAmount(): bool
    {
        return $this->state->canUpdateAmount();
    }

    /**
     * Get current state name
     */
    public function getStateName(): string
    {
        return $this->state->getStateName();
    }

    /**
     * Get current state description
     */
    public function getStateDescription(): string
    {
        return $this->state->getStateDescription();
    }

    /**
     * Get state transition history (for logging)
     */
    public function getStateInfo(): array
    {
        return [
            'fine_id' => $this->fine->id,
            'current_state' => $this->getStateName(),
            'description' => $this->getStateDescription(),
            'can_pay' => $this->canPay(),
            'can_waive' => $this->canWaive(),
            'can_update_amount' => $this->canUpdateAmount(),
            'amount' => $this->fine->amount,
            'user_id' => $this->fine->user_id,
        ];
    }
}
