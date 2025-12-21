# Auto Email Reminder System for Due Books

## Overview

The BookHub Library System includes an automated email reminder system that notifies users about:
- Books approaching their due date (3 days, 1 day, and day of)
- Overdue books (1, 3, 7, 14 days and weekly thereafter)

This system runs automatically via Laravel's Task Scheduler and can also be manually triggered by administrators.

---

## Features

### 1. Due Date Reminders
- **3 days before**: Friendly advance notice
- **1 day before**: Reminder to return soon
- **Day of**: Urgent notice that book is due today

### 2. Overdue Reminders
- **1 day overdue**: Initial overdue notice
- **3 days overdue**: Follow-up reminder
- **7 days overdue**: Warning notice
- **14 days overdue**: Final warning
- **21+ days**: Weekly reminders until returned

### 3. Email Content
Each reminder includes:
- Book title, author, and ISBN
- Original due date
- Days until due / days overdue
- Current fine amount (for overdue books)
- Projected fine if not returned
- Library contact information and hours
- Fine policy details

---

## System Components

### Files Created

| File | Purpose |
|------|---------|
| `app/Notifications/BookDueReminderNotification.php` | Email template for books approaching due date |
| `app/Notifications/BookOverdueNotification.php` | Email template for overdue books |
| `app/Console/Commands/SendDueBookReminders.php` | Artisan command to process and send reminders |
| `app/Services/ReminderService.php` | Service class with reminder logic |
| `config/library.php` | Configuration file for library settings |

### Modified Files

| File | Changes |
|------|---------|
| `routes/console.php` | Added scheduler for daily reminder emails at 8:00 AM |
| `routes/api.php` | Added reminder management API endpoints |
| `app/Http/Controllers/Api/BorrowingApiController.php` | Added reminder API methods |

---

## Usage

### Automatic (Scheduled)

The system automatically runs daily at 8:00 AM. To enable the scheduler, add this cron entry to your server:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or on Windows, create a Task Scheduler entry that runs every minute:
```
php artisan schedule:run
```

### Manual Commands

#### Preview reminders (dry run)
```bash
php artisan reminders:send-due-books --dry-run
```

#### Send reminders immediately
```bash
php artisan reminders:send-due-books
```

#### Check scheduler status
```bash
php artisan schedule:list
```

---

## API Endpoints

All endpoints require authentication and staff/admin privileges.

### GET /api/reminders/statistics
Get overview of books due and overdue.

**Response:**
```json
{
  "success": true,
  "data": {
    "books_due_today": 5,
    "books_due_within_3_days": 12,
    "overdue_books": 8,
    "severely_overdue": 2
  }
}
```

### GET /api/reminders/preview
Preview what reminders would be sent.

**Response:**
```json
{
  "success": true,
  "data": {
    "due_reminders": [...],
    "overdue_reminders": [...],
    "summary": {
      "total_due_reminders": 10,
      "total_overdue_reminders": 5,
      "total": 15
    }
  }
}
```

### GET /api/reminders/configuration
Get current reminder configuration.

### POST /api/reminders/send
Manually trigger reminders.

**Query Parameters:**
- `dry_run` (boolean): If true, simulates sending without actually sending emails

---

## Configuration

Edit `config/library.php` to customize settings:

```php
// Days before due date to send reminders
'reminder_days_before' => [3, 1, 0],

// Days after due date to send overdue reminders
'overdue_reminder_days' => [1, 3, 7, 14, 21, 28, 35, 42, 49],

// Fine settings
'fine_rate_per_day' => 0.50,
'max_fine' => 50.00,

// Time to send daily reminders (24-hour format)
'reminder_send_time' => '08:00',
```

### Environment Variables

Add to your `.env` file:
```env
# Library Settings
LIBRARY_FINE_RATE_PER_DAY=0.50
LIBRARY_MAX_FINE=50.00
LIBRARY_REMINDER_TIME=08:00
```

---

## Email Templates

### Due Reminder Email
- Subject: "Book Due Reminder: X days left - BookHub Library"
- Includes: Book details, due date, library hours
- Tone: Friendly reminder

### Overdue Email
- Subject: "⚠️ Book Overdue by X days - BookHub Library"
- Includes: Book details, fine amount, projected fines
- Tone: Urgent, escalating based on days overdue

---

## Testing

### Send Test Emails

1. Create a test borrowing with a due date matching today or past:
```sql
INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status) 
VALUES (1, 1, NOW() - INTERVAL 14 DAY, NOW() - INTERVAL 1 DAY, 'borrowed');
```

2. Run dry-run to preview:
```bash
php artisan reminders:send-due-books --dry-run
```

3. Send actual emails:
```bash
php artisan reminders:send-due-books
```

### Check Mail Log

If using `log` mail driver, check:
```
storage/logs/laravel.log
```

---

## Scheduling Details

The reminder command is scheduled with the following settings:

```php
Schedule::command('reminders:send-due-books')
    ->daily()
    ->at('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(fn() => Log::info('Due book reminders sent'))
    ->onFailure(fn() => Log::error('Failed to send reminders'));
```

- **Time**: 8:00 AM daily
- **Overlap Prevention**: Only one instance runs at a time
- **Background**: Runs asynchronously
- **Logging**: Success/failure logged to Laravel logs

---

## Troubleshooting

### Emails not sending

1. Check mail configuration in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

2. Test mail configuration:
```bash
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@email.com'));
```

3. Check logs for errors:
```bash
tail -f storage/logs/laravel.log
```

### Scheduler not running

1. Verify cron is set up correctly
2. Check `php artisan schedule:list` for registered tasks
3. Run manually: `php artisan schedule:run`

### No emails being sent in dry-run

This is expected behavior. Dry-run only shows what would be sent without actually sending.

---

## Security Notes

- Only active users with verified emails receive reminders
- API endpoints require authentication + staff role
- Email content does not include sensitive information
- Fine amounts are calculated server-side

---

## Related Documentation

- [Fine Processing System](./app/Console/Commands/ProcessOverdueFines.php)
- [Reservation Expiry](./app/Console/Commands/CheckExpiredReservations.php)
- [Email Configuration](./EMAIL_VERIFICATION_SETUP.md)
