<?php

namespace App\Notifications;

use App\Models\Borrowing;
use App\Models\Fine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to users when their borrowed book is overdue.
 * Includes information about accumulated fines and urges immediate return.
 */
class BookOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Borrowing $borrowing;
    protected int $daysOverdue;
    protected float $fineAmount;

    /**
     * Create a new notification instance.
     */
    public function __construct(Borrowing $borrowing, int $daysOverdue, float $fineAmount = 0)
    {
        $this->borrowing = $borrowing;
        $this->daysOverdue = $daysOverdue;
        $this->fineAmount = $fineAmount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $book = $this->borrowing->book;
        $dueDate = $this->borrowing->due_date->format('l, F j, Y');
        $dayText = $this->daysOverdue === 1 ? 'day' : 'days';

        // Determine urgency level for subject line
        $urgencyPrefix = $this->daysOverdue >= 7 ? '🚨 URGENT: ' : '⚠️ ';
        $subject = $urgencyPrefix . "Book Overdue by {$this->daysOverdue} {$dayText}";

        $message = (new MailMessage)
            ->subject($subject . ' - BookHub Library')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("🚨 **Your borrowed book is overdue by {$this->daysOverdue} {$dayText}!**")
            ->line('---')
            ->line('**Book Details:**')
            ->line("📖 **Title:** {$book->title}")
            ->line("✍️ **Author:** {$book->author}")
            ->line("🔢 **ISBN:** {$book->isbn}")
            ->line("📅 **Original Due Date:** {$dueDate}")
            ->line("⏰ **Days Overdue:** {$this->daysOverdue} {$dayText}")
            ->line("🆔 **Borrowing ID:** #{$this->borrowing->id}")
            ->line('---');

        // Show fine information
        if ($this->fineAmount > 0) {
            $message->line('**Current Fine:**')
                ->line("💰 **Amount Due:** RM " . number_format($this->fineAmount, 2))
                ->line('---');
        }

        // Calculate projected fine if not returned
        $projectedFine = min(($this->daysOverdue + 7) * 0.50, 50.00);

        $message->line('**⚠️ Important Notice:**')
            ->line('• Fines accumulate at **RM 0.50 per day** (max RM 50.00)')
            ->line("• If not returned in 7 more days, your fine could reach: **RM " . number_format($projectedFine, 2) . "**")
            ->line('• Unpaid fines may affect your borrowing privileges')
            ->line('---');

        if ($this->daysOverdue >= 14) {
            $message->line('🚨 **FINAL WARNING:** Your book is significantly overdue. Please return it immediately to avoid further penalties and potential account restrictions.');
        } elseif ($this->daysOverdue >= 7) {
            $message->line('⚠️ **WARNING:** Please return the book as soon as possible. Continued delays may result in borrowing restrictions.');
        } else {
            $message->line('Please return the book at your earliest convenience to minimize additional fines.');
        }

        $message->line('---')
            ->line('**Library Hours:**')
            ->line('Monday - Friday: 8:00 AM - 9:00 PM')
            ->line('Saturday: 9:00 AM - 5:00 PM')
            ->line('Sunday: Closed')
            ->line('---')
            ->line('If you have already returned this book, please disregard this email and contact the library to update your records.')
            ->salutation('BookHub Library Team');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'borrowing_id' => $this->borrowing->id,
            'book_id' => $this->borrowing->book_id,
            'book_title' => $this->borrowing->book->title,
            'due_date' => $this->borrowing->due_date->toDateString(),
            'days_overdue' => $this->daysOverdue,
            'fine_amount' => $this->fineAmount,
            'type' => 'overdue_notification',
        ];
    }
}
