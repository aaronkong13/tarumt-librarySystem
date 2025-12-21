<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to users when their borrowed book is approaching its due date.
 * This serves as a friendly reminder before the book becomes overdue.
 */
class BookDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Borrowing $borrowing;
    protected int $daysUntilDue;

    /**
     * Create a new notification instance.
     */
    public function __construct(Borrowing $borrowing, int $daysUntilDue)
    {
        $this->borrowing = $borrowing;
        $this->daysUntilDue = $daysUntilDue;
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

        $dayText = $this->daysUntilDue === 1 ? 'day' : 'days';
        $subject = $this->daysUntilDue === 0
            ? "Book Due Today: {$book->title}"
            : "Book Due Reminder: {$this->daysUntilDue} {$dayText} left";

        $message = (new MailMessage)
            ->subject($subject . ' - BookHub Library')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->daysUntilDue === 0
                ? '⚠️ Your borrowed book is due **today**!'
                : "📚 This is a friendly reminder that your borrowed book is due in **{$this->daysUntilDue} {$dayText}**.")
            ->line('---')
            ->line('**Book Details:**')
            ->line("📖 **Title:** {$book->title}")
            ->line("✍️ **Author:** {$book->author}")
            ->line("🔢 **ISBN:** {$book->isbn}")
            ->line("📅 **Due Date:** {$dueDate}")
            ->line("🆔 **Borrowing ID:** #{$this->borrowing->id}")
            ->line('---');

        if ($this->daysUntilDue === 0) {
            $message->line('⚠️ **Please return the book today to avoid late fees.**');
        } else {
            $message->line('Please return the book on or before the due date to avoid late fees.');
        }

        $message->line('**Late Fee Policy:**')
            ->line('• RM 0.50 per day for overdue books')
            ->line('• Maximum fine: RM 50.00 per book')
            ->line('---')
            ->line('If you need more time, please visit the library to request an extension (subject to availability).')
            ->salutation('Best regards, BookHub Library Team');

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
            'days_until_due' => $this->daysUntilDue,
            'type' => 'due_reminder',
        ];
    }
}
