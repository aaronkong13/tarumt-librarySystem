<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Library System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the library system,
    | including fine rates, reminder schedules, and borrowing limits.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Fine Settings
    |--------------------------------------------------------------------------
    */

    // Fine rate per day for overdue books (in RM)
    'fine_rate_per_day' => env('LIBRARY_FINE_RATE_PER_DAY', 0.50),

    // Maximum fine amount per book (in RM)
    'max_fine' => env('LIBRARY_MAX_FINE', 50.00),

    /*
    |--------------------------------------------------------------------------
    | Due Date Reminder Settings
    |--------------------------------------------------------------------------
    |
    | Configure when to send reminders before a book's due date.
    | Values are days before the due date.
    | Example: [3, 1, 0] means reminders at 3 days, 1 day, and day of due date.
    |
    */

    'reminder_days_before' => [3, 1, 0],

    /*
    |--------------------------------------------------------------------------
    | Overdue Reminder Settings
    |--------------------------------------------------------------------------
    |
    | Configure when to send reminders after a book becomes overdue.
    | Values are days after the due date.
    | Books beyond the last value will receive weekly reminders.
    |
    */

    'overdue_reminder_days' => [1, 3, 7, 14, 21, 28, 35, 42, 49],

    /*
    |--------------------------------------------------------------------------
    | Borrowing Limits
    |--------------------------------------------------------------------------
    */

    // Maximum number of books a student can borrow at once
    'max_books_per_student' => env('LIBRARY_MAX_BOOKS_PER_STUDENT', 5),

    // Default borrowing period in days
    'default_borrow_days' => env('LIBRARY_DEFAULT_BORROW_DAYS', 14),

    // Maximum renewals allowed per book
    'max_renewals' => env('LIBRARY_MAX_RENEWALS', 2),

    /*
    |--------------------------------------------------------------------------
    | Reservation Settings
    |--------------------------------------------------------------------------
    */

    // Maximum number of reservations per student
    'max_reservations_per_student' => env('LIBRARY_MAX_RESERVATIONS', 3),

    // Reservation expiry hours after book becomes available
    'reservation_expiry_hours' => env('LIBRARY_RESERVATION_EXPIRY_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    */

    // Time to send daily reminder emails (24-hour format)
    'reminder_send_time' => env('LIBRARY_REMINDER_TIME', '08:00'),

    // Library operating hours (for email templates)
    'operating_hours' => [
        'weekday' => 'Monday - Friday: 8:00 AM - 9:00 PM',
        'saturday' => 'Saturday: 9:00 AM - 5:00 PM',
        'sunday' => 'Sunday: Closed',
    ],

    // Library contact information
    'contact' => [
        'email' => env('LIBRARY_CONTACT_EMAIL', 'library@tarumt.edu.my'),
        'phone' => env('LIBRARY_CONTACT_PHONE', '+60-3-12345678'),
    ],
];
