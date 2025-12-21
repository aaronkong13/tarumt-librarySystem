<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent to users when they successfully borrow a book.
 * Includes book details, due date, and library policies.
 */
class BookBorrowedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Borrowing $borrowing;

    /**
     * Create a new notification instance.
     */
    public function __construct(Borrowing $borrowing)
    {
        $this->borrowing = $borrowing;
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
        $borrowDate = $this->borrowing->borrow_date->format('l, F j, Y');
        $dueDate = $this->borrowing->due_date->format('l, F j, Y');
        $daysUntilDue = $this->borrowing->borrow_date->diffInDays($this->borrowing->due_date);

        return (new MailMessage)
            ->subject("Book Borrowed Successfully: {$book->title} - BookHub Library")
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('✅ You have successfully borrowed a book from BookHub Library.')
            ->line('---')
            ->line('**Book Details:**')
            ->line("📖 **Title:** {$book->title}")
            ->line("✍️ **Author:** {$book->author}")
            ->line("🔢 **ISBN:** {$book->isbn}")
            ->line("📚 **Category:** {$book->category}")
            ->line('---')
            ->line('**Borrowing Information:**')
            ->line("📅 **Borrow Date:** {$borrowDate}")
            ->line("⏰ **Due Date:** {$dueDate}")
            ->line("📆 **Loan Period:** {$daysUntilDue} days")
            ->line("🆔 **Borrowing ID:** #{$this->borrowing->id}")
            ->line('---')
            ->line('**📋 Important Reminders:**')
            ->line('• Please return the book on or before the due date')
            ->line('• Late returns incur a fine of **RM 0.50 per day** (max RM 50.00)')
            ->line('• You can renew your book online if no one has reserved it')
            ->line('• Handle the book with care to avoid damage fees')
            ->line('---')
            ->line('**Library Hours:**')
            ->line('Monday - Friday: 8:00 AM - 9:00 PM')
            ->line('Saturday: 9:00 AM - 5:00 PM')
            ->line('Sunday: Closed')
            ->line('---')
            ->line('Thank you for using BookHub Library. Happy reading! 📚')
            ->salutation('Best regards, BookHub Library Team');
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
            'borrow_date' => $this->borrowing->borrow_date->toDateString(),
            'due_date' => $this->borrowing->due_date->toDateString(),
            'type' => 'book_borrowed',
        ];
    }
}
