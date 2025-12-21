<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\Fine;
use App\Notifications\BookDueReminderNotification;
use App\Notifications\BookOverdueNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling book due date and overdue reminder notifications.
 *
 * This service handles:
 * - Finding books approaching their due date
 * - Finding overdue books
 * - Sending appropriate email notifications to users
 * - Tracking notification history (if enabled)
 */
class ReminderService
{
    /**
     * Days before due date to send reminders (configurable)
     */
    protected array $reminderDaysBefore;

    /**
     * Days after due date to send overdue reminders
     */
    protected array $overdueReminderDays;

    /**
     * Fine rate per day for overdue books
     */
    protected float $fineRatePerDay;

    /**
     * Maximum fine amount per book
     */
    protected float $maxFine;

    public function __construct()
    {
        // Load configuration with defaults
        $this->reminderDaysBefore = config('library.reminder_days_before', [3, 1, 0]);
        $this->overdueReminderDays = config('library.overdue_reminder_days', [1, 3, 7, 14, 21, 28, 35, 42, 49]);
        $this->fineRatePerDay = config('library.fine_rate_per_day', 0.50);
        $this->maxFine = config('library.max_fine', 50.00);
    }

    /**
     * Get all borrowings approaching their due date
     *
     * @param int $daysBeforeDue Days until due date
     * @return Collection
     */
    public function getBorrowingsDueSoon(int $daysBeforeDue): Collection
    {
        $targetDate = Carbon::today()->addDays($daysBeforeDue);

        return Borrowing::with(['user', 'book'])
            ->where('status', 'borrowed')
            ->whereDate('due_date', $targetDate)
            ->whereHas('user', function ($query) {
                $query->where('status', 'Active')
                      ->whereNotNull('email_verified_at');
            })
            ->get();
    }

    /**
     * Get all overdue borrowings for a specific number of days overdue
     *
     * @param int $daysOverdue Number of days past due date
     * @return Collection
     */
    public function getOverdueBorrowings(int $daysOverdue): Collection
    {
        $targetDate = Carbon::today()->subDays($daysOverdue);

        return Borrowing::with(['user', 'book', 'fines'])
            ->where('status', 'borrowed')
            ->whereDate('due_date', $targetDate)
            ->whereHas('user', function ($query) {
                $query->where('status', 'Active')
                      ->whereNotNull('email_verified_at');
            })
            ->get();
    }

    /**
     * Get all severely overdue borrowings (beyond the standard reminder schedule)
     *
     * @param int $minDaysOverdue Minimum days overdue to include
     * @return Collection
     */
    public function getSeverelyOverdueBorrowings(int $minDaysOverdue = 49): Collection
    {
        return Borrowing::with(['user', 'book', 'fines'])
            ->where('status', 'borrowed')
            ->where('due_date', '<', Carbon::today()->subDays($minDaysOverdue))
            ->whereHas('user', function ($query) {
                $query->where('status', 'Active')
                      ->whereNotNull('email_verified_at');
            })
            ->get();
    }

    /**
     * Send due date reminder notification
     *
     * @param Borrowing $borrowing
     * @param int $daysUntilDue
     * @return bool Success status
     */
    public function sendDueReminder(Borrowing $borrowing, int $daysUntilDue): bool
    {
        try {
            $user = $borrowing->user;

            if (!$user || !$borrowing->book) {
                Log::warning("Cannot send due reminder for borrowing #{$borrowing->id}: missing user or book");
                return false;
            }

            $user->notify(new BookDueReminderNotification($borrowing, $daysUntilDue));

            Log::info("Due reminder sent", [
                'borrowing_id' => $borrowing->id,
                'user_email' => $user->email,
                'book_title' => $borrowing->book->title,
                'days_until_due' => $daysUntilDue,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send due reminder", [
                'borrowing_id' => $borrowing->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send overdue notification
     *
     * @param Borrowing $borrowing
     * @param int $daysOverdue
     * @return bool Success status
     */
    public function sendOverdueReminder(Borrowing $borrowing, int $daysOverdue): bool
    {
        try {
            $user = $borrowing->user;

            if (!$user || !$borrowing->book) {
                Log::warning("Cannot send overdue reminder for borrowing #{$borrowing->id}: missing user or book");
                return false;
            }

            // Get current fine amount
            $fineAmount = $this->calculateFineAmount($borrowing, $daysOverdue);

            $user->notify(new BookOverdueNotification($borrowing, $daysOverdue, $fineAmount));

            Log::info("Overdue reminder sent", [
                'borrowing_id' => $borrowing->id,
                'user_email' => $user->email,
                'book_title' => $borrowing->book->title,
                'days_overdue' => $daysOverdue,
                'fine_amount' => $fineAmount,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send overdue reminder", [
                'borrowing_id' => $borrowing->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calculate fine amount for a borrowing
     *
     * @param Borrowing $borrowing
     * @param int $daysOverdue
     * @return float
     */
    public function calculateFineAmount(Borrowing $borrowing, int $daysOverdue): float
    {
        // Check for existing unpaid fine
        $existingFine = $borrowing->fines()
            ->where('status', Fine::STATUS_UNPAID)
            ->sum('amount');

        if ($existingFine > 0) {
            return (float) $existingFine;
        }

        // Calculate based on days overdue
        return min($daysOverdue * $this->fineRatePerDay, $this->maxFine);
    }

    /**
     * Process all due date reminders
     *
     * @return array Results with counts
     */
    public function processAllDueReminders(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($this->reminderDaysBefore as $daysBeforeDue) {
            $borrowings = $this->getBorrowingsDueSoon($daysBeforeDue);

            foreach ($borrowings as $borrowing) {
                $success = $this->sendDueReminder($borrowing, $daysBeforeDue);

                if ($success) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'borrowing_id' => $borrowing->id,
                    'type' => 'due_reminder',
                    'days_until_due' => $daysBeforeDue,
                    'success' => $success,
                ];
            }
        }

        return $results;
    }

    /**
     * Process all overdue reminders
     *
     * @return array Results with counts
     */
    public function processAllOverdueReminders(): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($this->overdueReminderDays as $daysOverdue) {
            $borrowings = $this->getOverdueBorrowings($daysOverdue);

            foreach ($borrowings as $borrowing) {
                $success = $this->sendOverdueReminder($borrowing, $daysOverdue);

                if ($success) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'borrowing_id' => $borrowing->id,
                    'type' => 'overdue_reminder',
                    'days_overdue' => $daysOverdue,
                    'success' => $success,
                ];
            }
        }

        // Process severely overdue books (weekly reminders)
        $this->processSeverelyOverdueReminders($results);

        return $results;
    }

    /**
     * Process severely overdue books with weekly reminders
     */
    protected function processSeverelyOverdueReminders(array &$results): void
    {
        $borrowings = $this->getSeverelyOverdueBorrowings();
        $today = Carbon::today();

        foreach ($borrowings as $borrowing) {
            $daysOverdue = $borrowing->due_date->diffInDays($today);

            // Only send on weekly intervals
            if ($daysOverdue % 7 !== 0) {
                continue;
            }

            $success = $this->sendOverdueReminder($borrowing, $daysOverdue);

            if ($success) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = [
                'borrowing_id' => $borrowing->id,
                'type' => 'severely_overdue',
                'days_overdue' => $daysOverdue,
                'success' => $success,
            ];
        }
    }

    /**
     * Get reminder statistics for reporting
     *
     * @return array
     */
    public function getReminderStatistics(): array
    {
        $today = Carbon::today();

        return [
            'books_due_today' => Borrowing::where('status', 'borrowed')
                ->whereDate('due_date', $today)
                ->count(),
            'books_due_within_3_days' => Borrowing::where('status', 'borrowed')
                ->whereDate('due_date', '>', $today)
                ->whereDate('due_date', '<=', $today->copy()->addDays(3))
                ->count(),
            'overdue_books' => Borrowing::where('status', 'borrowed')
                ->whereDate('due_date', '<', $today)
                ->count(),
            'severely_overdue' => Borrowing::where('status', 'borrowed')
                ->whereDate('due_date', '<', $today->copy()->subDays(14))
                ->count(),
        ];
    }
}
