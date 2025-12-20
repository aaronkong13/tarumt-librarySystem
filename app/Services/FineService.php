<?php

namespace App\Services;

use App\Models\Fine;
use App\Models\Borrowing;
use App\Models\User;
use App\States\Borrowing\BorrowingContext;
use App\States\Fine\FineContext;
use App\Services\FineSecurityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * FineService - Centralized business logic for fine operations
 *
 * Design Patterns Used:
 * - State Pattern: FineContext manages fine states (Unpaid → Paid/Waived)
 * - Service Pattern: All business logic centralized here
 *
 * Security Implementation (OWASP Checklist):
 * - [1] Input Validation: Via FineSecurityService
 * - [107] Error Handling and Logging: Comprehensive logging
 * - [131] Data Protection: Sensitive data masking
 *
 * Architecture mirrors BorrowingService for consistency.
 * All business logic is here - controllers just handle HTTP concerns.
 */
class FineService
{
    protected $fineRatePerDay;
    protected $maxFinePerBook;
    protected $gracePeriodDays;
    protected FineSecurityService $securityService;

    public function __construct()
    {
        $this->securityService = new FineSecurityService();
        $this->fineRatePerDay = config('library.fines.rate_per_day', 0.50);
        $this->maxFinePerBook = config('library.fines.max_per_book', 50.00);
        $this->gracePeriodDays = config('library.fines.grace_period_days', 0);
    }

    /**
     * Calculate fine for a borrowing
     */
    public function calculateFine(Borrowing $borrowing): float
    {
        if (!$borrowing->isOverdue()) {
            return 0;
        }

        $daysOverdue = $borrowing->getDaysOverdue() - $this->gracePeriodDays;

        if ($daysOverdue <= 0) {
            return 0;
        }

        $fineAmount = $daysOverdue * $this->fineRatePerDay;

        // Cap the fine at maximum
        return min($fineAmount, $this->maxFinePerBook);
    }

    /**
     * Create fine for overdue borrowing
     */
    public function createFineForOverdue($borrowingId, $reason = null)
    {
        return DB::transaction(function () use ($borrowingId, $reason) {
            $borrowing = Borrowing::with(['book', 'user'])->findOrFail($borrowingId);

            // Check if fine already exists for this borrowing (any status)
            $existingFine = Fine::where('borrowing_id', $borrowingId)->first();

            if ($existingFine) {
                // Only update if unpaid and amount increased
                if ($existingFine->status === 'unpaid') {
                    $newAmount = $this->calculateFine($borrowing);
                    if ($newAmount > $existingFine->amount) {
                        $existingFine->update([
                            'amount' => $newAmount,
                            'reason' => $reason ?? "Overdue by {$borrowing->getDaysOverdue()} days",
                        ]);

                        Log::info('Fine amount updated', [
                            'fine_id' => $existingFine->id,
                            'old_amount' => $existingFine->amount,
                            'new_amount' => $newAmount,
                        ]);
                    }
                }
                // Return existing fine (don't create duplicate)
                return $existingFine->fresh();
            }

            $fineAmount = $this->calculateFine($borrowing);

            if ($fineAmount <= 0) {
                return null;
            }

            $daysOverdue = $borrowing->getDaysOverdue();
            $reason = $reason ?? "Overdue by {$daysOverdue} days";

            $fine = Fine::create([
                'user_id' => $borrowing->user_id,
                'borrowing_id' => $borrowing->id,
                'amount' => $fineAmount,
                'reason' => $reason,
                'status' => 'unpaid',
            ]);

            Log::info('Fine created for overdue', [
                'fine_id' => $fine->id,
                'borrowing_id' => $borrowingId,
                'user_id' => $borrowing->user_id,
                'days_overdue' => $daysOverdue,
                'amount' => $fineAmount,
            ]);

            return $fine->load(['user', 'borrowing.book']);
        });
    }

    /**
     * Pay fine - Using State Pattern
     *
     * State Transition: Unpaid → Paid
     * Security: Input validation via FineSecurityService
     */
    public function payFine($fineId, $paymentMethod = 'cash', $notes = null)
    {
        // [1] Input Validation
        $validated = $this->securityService->validatePaymentInput([
            'fine_id' => $fineId,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);

        return DB::transaction(function () use ($fineId, $validated) {
            $fine = Fine::with(['borrowing.book', 'user'])->findOrFail($fineId);

            // === STATE PATTERN: Create context and check state ===
            $fineContext = new FineContext($fine);

            Log::info('Fine State Pattern [PAY]: Checking fine state', [
                'fine_id' => $fine->id,
                'current_state' => $fineContext->getStateName(),
                'can_pay' => $fineContext->canPay()
            ]);

            // Check if fine can be paid based on current state
            if (!$fineContext->canPay()) {
                throw new Exception("Cannot pay fine. Current state: {$fineContext->getStateName()}");
            }

            // === STATE PATTERN: Perform state transition ===
            $fineContext->pay();

            // Get borrowing state info for logging
            $borrowingStateInfo = 'N/A';
            if ($fine->borrowing && $fine->borrowing->book) {
                $borrowingContext = new BorrowingContext($fine->borrowing->book, $fine->borrowing);
                $borrowingStateInfo = $borrowingContext->getStateName();
            }

            // Update fine as paid
            $fine->update([
                'status' => 'paid',
                'paid_date' => now(),
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'notes' => $validated['notes'] ?? null,
            ]);

            // [107] Logging: Log payment transaction
            $this->securityService->logPaymentTransaction(
                $fine,
                $validated['payment_method'] ?? 'cash',
                auth()->id()
            );

            // Log when all fines for a borrowing are paid
            if ($fine->borrowing) {
                $unpaidFinesCount = Fine::where('borrowing_id', $fine->borrowing_id)
                    ->where('status', 'unpaid')
                    ->count();

                if ($unpaidFinesCount === 0) {
                    Log::info('All fines paid for borrowing', ['borrowing_id' => $fine->borrowing_id]);
                }
            }

            Log::info('Fine State Pattern [PAY]: State transition completed', [
                'fine_id' => $fineId,
                'user_id' => $fine->user_id,
                'amount' => $fine->amount,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'new_fine_state' => $fineContext->getStateName(),
                'borrowing_state' => $borrowingStateInfo,
            ]);

            return $fine->fresh(['borrowing.book', 'user']);
        });
    }

    /**
     * Waive fine (Admin/Staff only) - Using State Pattern
     *
     * State Transition: Unpaid → Waived
     * Security: Input validation and authorization via FineSecurityService
     */
    public function waiveFine($fineId, $reason = null)
    {
        // [1] Input Validation
        $validated = $this->securityService->validateWaiveInput([
            'fine_id' => $fineId,
            'reason' => $reason,
        ]);

        return DB::transaction(function () use ($fineId, $validated) {
            $fine = Fine::with(['borrowing.book', 'user'])->findOrFail($fineId);

            // === STATE PATTERN: Create context and check state ===
            $fineContext = new FineContext($fine);

            Log::info('Fine State Pattern [WAIVE]: Checking fine state', [
                'fine_id' => $fine->id,
                'current_state' => $fineContext->getStateName(),
                'can_waive' => $fineContext->canWaive()
            ]);

            // Check if fine can be waived based on current state
            if (!$fineContext->canWaive()) {
                throw new Exception("Cannot waive fine. Current state: {$fineContext->getStateName()}");
            }

            // === STATE PATTERN: Perform state transition ===
            $fineContext->waive();

            $fine->update([
                'status' => 'waived',
                'notes' => $validated['reason'] ?? 'Fine waived by staff',
            ]);

            // [107] Logging: Log waive activity
            $this->securityService->logFineActivity('waive', $fine, [
                'reason' => $validated['reason'] ?? 'No reason provided',
                'waived_by' => auth()->id(),
            ]);

            // Log when all fines for a borrowing are waived
            if ($fine->borrowing) {
                $unpaidFinesCount = Fine::where('borrowing_id', $fine->borrowing_id)
                    ->where('status', 'unpaid')
                    ->count();

                if ($unpaidFinesCount === 0) {
                    Log::info('All fines cleared for borrowing', ['borrowing_id' => $fine->borrowing_id]);
                }
            }

            Log::info('Fine State Pattern [WAIVE]: State transition completed', [
                'fine_id' => $fineId,
                'user_id' => $fine->user_id,
                'amount' => $fine->amount,
                'new_fine_state' => $fineContext->getStateName(),
                'reason' => $validated['reason'] ?? 'Fine waived by staff',
            ]);

            return $fine->fresh(['borrowing.book', 'user']);
        });
    }

    /**
     * Get all fines with state information
     * Returns Fine models (not arrays) for view compatibility
     */
    public function getAllFinesWithStates($status = null)
    {
        $query = Fine::with(['borrowing.book', 'borrowing.user', 'user']);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get user's fines with state information
     * Returns Fine models (not arrays) for view compatibility
     */
    public function getUserFinesWithStates($userId, $status = null)
    {
        $query = Fine::with(['borrowing.book', 'user'])
            ->where('user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get fine summary for a user
     */
    public function getUserFineSummary($userId)
    {
        $fines = Fine::where('user_id', $userId)->get();

        return [
            'total_fines' => $fines->count(),
            'unpaid_count' => $fines->where('status', 'unpaid')->count(),
            'paid_count' => $fines->where('status', 'paid')->count(),
            'waived_count' => $fines->where('status', 'waived')->count(),
            'total_unpaid_amount' => $fines->where('status', 'unpaid')->sum('amount'),
            'total_paid_amount' => $fines->where('status', 'paid')->sum('amount'),
            'total_waived_amount' => $fines->where('status', 'waived')->sum('amount'),
        ];
    }

    /**
     * Get system-wide fine statistics
     */
    public function getFineStatistics()
    {
        $fines = Fine::all();

        return [
            'total_fines' => $fines->count(),
            'unpaid_fines' => [
                'count' => $fines->where('status', 'unpaid')->count(),
                'amount' => $fines->where('status', 'unpaid')->sum('amount'),
            ],
            'paid_fines' => [
                'count' => $fines->where('status', 'paid')->count(),
                'amount' => $fines->where('status', 'paid')->sum('amount'),
            ],
            'waived_fines' => [
                'count' => $fines->where('status', 'waived')->count(),
                'amount' => $fines->where('status', 'waived')->sum('amount'),
            ],
            'fine_rate_per_day' => $this->fineRatePerDay,
            'max_fine_per_book' => $this->maxFinePerBook,
        ];
    }

    /**
     * Process all overdue borrowings and create/update fines
     * This can be called by a scheduled job
     */
    public function processOverdueFines()
    {
        $overdueBorrowings = Borrowing::where('status', 'borrowed')
            ->where('due_date', '<', now())
            ->with(['book', 'user'])
            ->get();

        $processed = [];

        foreach ($overdueBorrowings as $borrowing) {
            try {
                $fine = $this->createFineForOverdue($borrowing->id);
                if ($fine) {
                    $processed[] = [
                        'borrowing_id' => $borrowing->id,
                        'fine_id' => $fine->id,
                        'amount' => $fine->amount,
                        'status' => 'created_or_updated',
                    ];
                }
            } catch (Exception $e) {
                Log::error('Failed to process overdue fine', [
                    'borrowing_id' => $borrowing->id,
                    'error' => $e->getMessage(),
                ]);
                $processed[] = [
                    'borrowing_id' => $borrowing->id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Overdue fines processed', [
            'total_overdue' => $overdueBorrowings->count(),
            'processed' => count($processed),
        ]);

        return $processed;
    }

    /**
     * Get fine by ID with state info
     */
    public function getFineById($fineId)
    {
        $fine = Fine::with(['borrowing.book', 'user'])->findOrFail($fineId);
        return $this->mapFineWithState($fine);
    }

    /**
     * Check if user has unpaid fines
     */
    public function userHasUnpaidFines($userId): bool
    {
        return Fine::where('user_id', $userId)
            ->where('status', 'unpaid')
            ->exists();
    }

    /**
     * Get total unpaid fines for user
     */
    public function getUserTotalUnpaidFines($userId): float
    {
        return Fine::where('user_id', $userId)
            ->where('status', 'unpaid')
            ->sum('amount');
    }

    /**
     * Pay all unpaid fines for a user
     */
    public function payAllUserFines($userId, $paymentMethod = 'cash')
    {
        return DB::transaction(function () use ($userId, $paymentMethod) {
            $unpaidFines = Fine::where('user_id', $userId)
                ->where('status', 'unpaid')
                ->get();

            if ($unpaidFines->isEmpty()) {
                throw new Exception('No unpaid fines found for this user.');
            }

            $totalAmount = $unpaidFines->sum('amount');
            $paidFines = [];

            foreach ($unpaidFines as $fine) {
                $fine->update([
                    'status' => 'paid',
                    'paid_date' => now(),
                    'payment_method' => $paymentMethod,
                ]);
                $paidFines[] = $fine->id;
            }

            // Log all related borrowings cleared
            $borrowingIds = $unpaidFines->pluck('borrowing_id')->unique();
            Log::info('All fines paid for borrowings', ['borrowing_ids' => $borrowingIds->toArray()]);

            Log::info('All user fines paid', [
                'user_id' => $userId,
                'fines_paid' => count($paidFines),
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
            ]);

            return [
                'fines_paid' => count($paidFines),
                'total_amount' => $totalAmount,
                'fine_ids' => $paidFines,
            ];
        });
    }

    /**
     * Map fine with state information (both Fine State and Borrowing State)
     *
     * Uses State Pattern: FineContext for fine state, BorrowingContext for borrowing state
     */
    protected function mapFineWithState(Fine $fine): array
    {
        // Fine State Pattern
        $fineContext = new FineContext($fine);
        $fineStateInfo = $fineContext->getStateInfo();

        // Borrowing State Pattern
        $borrowingStateInfo = 'N/A';
        if ($fine->borrowing && $fine->borrowing->book) {
            $borrowingContext = new BorrowingContext($fine->borrowing->book, $fine->borrowing);
            $borrowingStateInfo = $borrowingContext->getStateName();
        }

        return [
            'id' => $fine->id,
            'user' => $fine->user,
            'borrowing' => $fine->borrowing,
            'amount' => $fine->amount,
            'reason' => $fine->reason,
            'status' => $fine->status,
            'paid_date' => $fine->paid_date,
            'payment_method' => $fine->payment_method,
            'notes' => $fine->notes,
            'created_at' => $fine->created_at,
            'updated_at' => $fine->updated_at,
            // Fine State Pattern info
            'fine_state' => $fineContext->getStateName(),
            'fine_state_description' => $fineContext->getStateDescription(),
            // Borrowing State Pattern info
            'borrowing_state' => $borrowingStateInfo,
            'fine_origin' => 'Generated from Overdue state',
            // State-based permissions
            'can_pay' => $fineContext->canPay(),
            'can_waive' => $fineContext->canWaive(),
            'can_update_amount' => $fineContext->canUpdateAmount(),
        ];
    }

    /**
     * Get fines by date range
     */
    public function getFinesByDateRange($startDate, $endDate, $status = null)
    {
        $query = Fine::with(['borrowing.book', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($fine) {
                return $this->mapFineWithState($fine);
            });
    }

    /**
     * Generate fine report
     */
    public function generateFineReport($startDate = null, $endDate = null)
    {
        $query = Fine::with(['borrowing.book', 'user']);

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $fines = $query->get();

        return [
            'period' => [
                'start' => $startDate ?? 'All time',
                'end' => $endDate ?? 'Present',
            ],
            'summary' => [
                'total_fines' => $fines->count(),
                'total_amount' => $fines->sum('amount'),
                'unpaid' => [
                    'count' => $fines->where('status', 'unpaid')->count(),
                    'amount' => $fines->where('status', 'unpaid')->sum('amount'),
                ],
                'paid' => [
                    'count' => $fines->where('status', 'paid')->count(),
                    'amount' => $fines->where('status', 'paid')->sum('amount'),
                ],
                'waived' => [
                    'count' => $fines->where('status', 'waived')->count(),
                    'amount' => $fines->where('status', 'waived')->sum('amount'),
                ],
            ],
            'top_users' => $fines->groupBy('user_id')
                ->map(function ($userFines) {
                    return [
                        'user' => $userFines->first()->user,
                        'total_fines' => $userFines->count(),
                        'total_amount' => $userFines->sum('amount'),
                    ];
                })
                ->sortByDesc('total_amount')
                ->take(10)
                ->values(),
        ];
    }
}
