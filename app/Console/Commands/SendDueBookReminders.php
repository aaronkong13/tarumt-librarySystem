<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\Fine;
use App\Notifications\BookDueReminderNotification;
use App\Notifications\BookOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Console command to send email reminders for books that are:
 * - Approaching their due date (1, 3 days before)
 * - Due today
 * - Overdue (1, 3, 7, 14 days and weekly thereafter)
 *
 * This command should be scheduled to run daily via Laravel Scheduler.
 */
class SendDueBookReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-due-books
                            {--dry-run : Preview what emails would be sent without actually sending}
                            {--force : Send reminders even if already sent today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for books approaching due date or overdue';

    /**
     * Days before due date to send reminders
     */
    protected array $reminderDaysBefore = [3, 1, 0];

    /**
     * Days after due date (overdue) to send reminders
     * Sends at 1, 3, 7, 14 days overdue, then weekly
     */
    protected array $overdueReminderDays = [1, 3, 7, 14, 21, 28, 35, 42, 49];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No emails will be sent');
            $this->newLine();
        }

        $this->info('📧 Processing due book reminders...');
        $this->newLine();

        $dueRemindersSent = $this->processDueReminders($isDryRun);
        $overdueRemindersSent = $this->processOverdueReminders($isDryRun);

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('📊 SUMMARY');
        $this->info('═══════════════════════════════════════');
        $this->info("📅 Due date reminders: {$dueRemindersSent}");
        $this->info("⚠️  Overdue reminders: {$overdueRemindersSent}");
        $this->info("📧 Total emails " . ($isDryRun ? 'would be sent' : 'sent') . ": " . ($dueRemindersSent + $overdueRemindersSent));
        $this->newLine();
        $this->info('✅ Done!');

        return Command::SUCCESS;
    }

    /**
     * Process and send reminders for books approaching due date
     */
    protected function processDueReminders(bool $isDryRun): int
    {
        $this->info('📅 Checking for books approaching due date...');

        $remindersSent = 0;
        $today = Carbon::today();

        foreach ($this->reminderDaysBefore as $daysBeforeDue) {
            $targetDate = $today->copy()->addDays($daysBeforeDue);

            $borrowings = Borrowing::with(['user', 'book'])
                ->where('status', 'borrowed')
                ->whereDate('due_date', $targetDate)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Active')
                          ->whereNotNull('email_verified_at');
                })
                ->get();

            if ($borrowings->isEmpty()) {
                continue;
            }

            $dayLabel = $daysBeforeDue === 0 ? 'today' : "in {$daysBeforeDue} day(s)";
            $this->line("  Found {$borrowings->count()} book(s) due {$dayLabel}");

            foreach ($borrowings as $borrowing) {
                $user = $borrowing->user;
                $book = $borrowing->book;

                if (!$user || !$book) {
                    $this->warn("    ⚠️ Skipping borrowing #{$borrowing->id} - missing user or book data");
                    continue;
                }

                if ($isDryRun) {
                    $this->line("    📧 Would send to: {$user->email} - \"{$book->title}\" (Due {$dayLabel})");
                } else {
                    try {
                        $user->notify(new BookDueReminderNotification($borrowing, $daysBeforeDue));
                        $this->line("    ✅ Sent to: {$user->email} - \"{$book->title}\"");
                    } catch (\Exception $e) {
                        $this->error("    ❌ Failed to send to {$user->email}: {$e->getMessage()}");
                        continue;
                    }
                }

                $remindersSent++;
            }
        }

        if ($remindersSent === 0) {
            $this->line('  No upcoming due books found.');
        }

        return $remindersSent;
    }

    /**
     * Process and send reminders for overdue books
     */
    protected function processOverdueReminders(bool $isDryRun): int
    {
        $this->newLine();
        $this->info('⚠️  Checking for overdue books...');

        $remindersSent = 0;
        $today = Carbon::today();

        foreach ($this->overdueReminderDays as $daysOverdue) {
            $targetDate = $today->copy()->subDays($daysOverdue);

            $borrowings = Borrowing::with(['user', 'book', 'fines'])
                ->where('status', 'borrowed')
                ->whereDate('due_date', $targetDate)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Active')
                          ->whereNotNull('email_verified_at');
                })
                ->get();

            if ($borrowings->isEmpty()) {
                continue;
            }

            $this->line("  Found {$borrowings->count()} book(s) overdue by {$daysOverdue} day(s)");

            foreach ($borrowings as $borrowing) {
                $user = $borrowing->user;
                $book = $borrowing->book;

                if (!$user || !$book) {
                    $this->warn("    ⚠️ Skipping borrowing #{$borrowing->id} - missing user or book data");
                    continue;
                }

                // Get current fine amount for this borrowing
                $fineAmount = $borrowing->fines()
                    ->where('status', Fine::STATUS_UNPAID)
                    ->sum('amount');

                // If no fine record exists yet, calculate based on days overdue
                if ($fineAmount == 0) {
                    $fineAmount = min($daysOverdue * 0.50, 50.00);
                }

                if ($isDryRun) {
                    $this->line("    📧 Would send to: {$user->email} - \"{$book->title}\" ({$daysOverdue} days overdue, Fine: RM " . number_format($fineAmount, 2) . ")");
                } else {
                    try {
                        $user->notify(new BookOverdueNotification($borrowing, $daysOverdue, $fineAmount));
                        $this->line("    ✅ Sent to: {$user->email} - \"{$book->title}\" (Fine: RM " . number_format($fineAmount, 2) . ")");
                    } catch (\Exception $e) {
                        $this->error("    ❌ Failed to send to {$user->email}: {$e->getMessage()}");
                        continue;
                    }
                }

                $remindersSent++;
            }
        }

        // Also send weekly reminders for very overdue books (> 49 days)
        $this->processVeryOverdueBooks($isDryRun, $remindersSent);

        if ($remindersSent === 0) {
            $this->line('  No overdue books matching reminder schedule.');
        }

        return $remindersSent;
    }

    /**
     * Process books that are very overdue (> 49 days) and send weekly reminders
     */
    protected function processVeryOverdueBooks(bool $isDryRun, int &$remindersSent): void
    {
        $today = Carbon::today();

        // Get books overdue by more than 49 days where the days overdue is divisible by 7
        $borrowings = Borrowing::with(['user', 'book', 'fines'])
            ->where('status', 'borrowed')
            ->where('due_date', '<', $today->copy()->subDays(49))
            ->whereHas('user', function ($query) {
                $query->where('status', 'Active')
                      ->whereNotNull('email_verified_at');
            })
            ->get();

        foreach ($borrowings as $borrowing) {
            $daysOverdue = $borrowing->due_date->diffInDays($today);

            // Only send on weekly intervals (every 7 days after 49)
            if ($daysOverdue % 7 !== 0) {
                continue;
            }

            $user = $borrowing->user;
            $book = $borrowing->book;

            if (!$user || !$book) {
                continue;
            }

            $fineAmount = $borrowing->fines()
                ->where('status', Fine::STATUS_UNPAID)
                ->sum('amount');

            if ($fineAmount == 0) {
                $fineAmount = 50.00; // Max fine
            }

            if ($isDryRun) {
                $this->line("    📧 Would send to: {$user->email} - \"{$book->title}\" ({$daysOverdue} days overdue - SEVERELY OVERDUE)");
            } else {
                try {
                    $user->notify(new BookOverdueNotification($borrowing, $daysOverdue, $fineAmount));
                    $this->line("    ✅ Sent to: {$user->email} - \"{$book->title}\" (Severely overdue: {$daysOverdue} days)");
                } catch (\Exception $e) {
                    $this->error("    ❌ Failed to send to {$user->email}: {$e->getMessage()}");
                    continue;
                }
            }

            $remindersSent++;
        }
    }
}
