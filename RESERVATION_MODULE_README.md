# Book Reservation Module - Implementation Documentation

## ✅ Module Implementation Complete

This document outlines the complete implementation of the Book Reservation/Booking Module according to the Software Requirements Specification (SRS).

---

## 📋 Implementation Summary

### 1. Database Layer ✅

**Migration Files Created:**
- `2025_12_18_071702_add_notified_at_to_reservations_table.php`
  - Added `notified_at` timestamp field for tracking when user was notified

**Database Schema (reservations table):**
```sql
- id (primary key)
- user_id (foreign key to users)
- book_id (foreign key to books.bookId)
- reservation_date (datetime)
- expiry_date (datetime) - Set when notified
- notified_at (datetime) - Tracks notification time
- status (enum: 'waiting', 'notified', 'fulfilled', 'cancelled', 'expired')
- queue_position (integer) - FIFO queue position
- notes (text, nullable)
- timestamps
- soft_deletes
```

### 2. Models ✅

**Reservation Model Enhanced** (`app/Models/Reservation.php`)
- Status constants defined
- Enhanced relationships with User and Book
- Scopes: `active()`, `waiting()`, `notified()`, `expired()`, `orderedQueue()`
- Helper methods:
  - `isExpired()` - Check if notification expired
  - `isWaiting()` - Check if waiting in queue
  - `isNotified()` - Check if user was notified
  - `getRemainingDays()` - Get days until expiry
  - `getStatusLabel()` - Get human-readable status

### 3. Services Layer ✅

**ReservationService** (`app/Services/ReservationService.php`)

**Core Methods Implemented:**

1. **`reserveBook(userId, bookId)`** - Student reserves a book
   - Validates book is borrowed (not available)
   - Checks for duplicate reservations
   - Enforces max 5 active reservations per student
   - Calculates FIFO queue position
   - Uses parameterized queries (Laravel Eloquent ORM)

2. **`cancelReservation(reservationId, userId)`** - Cancel own reservation
   - Authorization check (only owner can cancel)
   - Auto re-orders queue after cancellation
   - Transaction-based for atomicity

3. **`getReservationQueue(bookId)`** - Staff view: Get full queue
   - Returns all active reservations ordered by position
   - Includes user details, status, expiry info

4. **`getUserReservations(userId)`** - Student view: Get own reservations
   - Shows queue position, status, remaining days
   - Indicates if cancellation is allowed

5. **`notifyNextInQueue(book)`** - Auto-notify next person (Observer Pattern)
   - Triggered when book is returned
   - Updates first waiting reservation to 'notified'
   - Sets 3-day expiry deadline
   - Sends notification via Observer Pattern

6. **`checkAndExpireReservations()`** - Scheduled task
   - Finds expired notifications (>3 days)
   - Marks as expired
   - Notifies next person in queue
   - Fully automated

7. **`fulfillReservation(userId, bookId)`** - Mark as fulfilled when borrowed
   - Called during borrow process
   - Updates status to 'fulfilled'

8. **`getUserNotifications(userId)`** - Get notified books for student
   - Shows books ready for pickup
   - Displays countdown timer

### 4. Controllers ✅

**ReservationController** (`app/Http/Controllers/ReservationController.php`)

**Student Endpoints:**
- `POST /reservations` - Reserve a book
- `DELETE /reservations/{id}` - Cancel reservation
- `GET /reservations/my-reservations` - View my reservations
- `GET /reservations/my-notifications` - View notifications
- `GET /reservations/check/{bookId}` - AJAX status check

**Staff Endpoints:**
- `GET /reservations/queue/{bookId}` - View queue for specific book
- `GET /reservations/all-queues` - View all reservation queues

**BorrowingController Integration:**
- Modified `return()` method to check for reservations
- If reservations exist, notifies next person and sets book to 'Reserved'
- Modified `borrow()` method to mark reservation as fulfilled

### 5. Observer Pattern Implementation ✅

**Existing Observer Infrastructure Used:**
- `ReservationSubject` - Subject that manages observers
- `EmailNotificationObserver` - Logs email notifications
- `LoggingObserver` - Logs reservation events

**Integration Points:**
1. **Book Returned** → `notifyNextInQueue()` → Observer notifies first person
2. **Reservation Fulfilled** → Observer logs completion
3. **Reservation Expired** → Observer logs expiration

### 6. Scheduled Task ✅

**Command Created:** `app/Console/Commands/CheckExpiredReservations.php`
- Command signature: `reservations:check-expired`
- Purpose: Check and expire old notifications (3-day rule)
- Scheduled: Every hour via `routes/console.php`
- Automatically notifies next person if book still available

**Scheduler Configuration:**
```php
Schedule::command('reservations:check-expired')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
```

### 7. Routes ✅

**Student Routes:**
```php
POST   /reservations                        - Reserve book
DELETE /reservations/{id}                   - Cancel reservation
GET    /reservations/my-reservations        - My reservations page
GET    /reservations/my-notifications       - Notifications page
GET    /reservations/check/{bookId}         - Check status (AJAX)
```

**Staff Routes:**
```php
GET    /reservations/queue/{bookId}         - View queue for book
GET    /reservations/all-queues             - View all queues
```

### 8. Frontend Views ✅

**Student Views Created:**

1. **`my-reservations.blade.php`**
   - Shows all active reservations
   - Displays queue position
   - Shows status (Waiting / Notified)
   - Countdown timer for notified books
   - Cancel button for eligible reservations

2. **`my-notifications.blade.php`**
   - Shows notified books (ready for pickup)
   - Visual countdown with color coding
   - Book cover images
   - "Go to Books & Borrow" link
   - Expired notification warnings

**Staff Views Created:**

3. **`queue.blade.php`**
   - Shows full reservation queue for a book
   - Ordered by queue position (FIFO)
   - User details (name, email, ID)
   - Status and notification timestamps
   - Expiry countdown

4. **`all-queues.blade.php`**
   - Overview of all books with reservations
   - Shows reservation count per book
   - Quick access to each queue

**UI Enhancements:**

5. **`sidebar.blade.php` Updated**
   - Students: "My Reservations" menu item
   - Staff/Admin: "Reservation Queues" menu item
   - Active state highlighting

6. **`book-cards.blade.php` Updated**
   - "Reserve This Book" button for borrowed books
   - Only visible to students
   - Form submits to reservation endpoint

---

## 🔒 Security Implementation

### 1. Parameterized Queries ✅
- All database operations use Laravel Eloquent ORM
- No raw SQL string concatenation
- Example: `Reservation::where('user_id', $userId)` (parameterized)

### 2. Input Validation (Whitelist-Based) ✅
```php
// In ReservationService
if (!is_numeric($userId) || !is_numeric($bookId)) {
    throw new Exception('Invalid user ID or book ID format.');
}

// In ReservationController
$validated = $request->validate([
    'book_id' => 'required|integer|exists:books,bookId',
]);
```

### 3. Authorization Checks ✅
- Students can only cancel their own reservations
- Staff routes protected by middleware: `check.staff`
- Authorization enforced in service layer:
```php
$reservation = Reservation::where('id', $reservationId)
    ->where('user_id', $userId) // Owner check
    ->firstOrFail();
```

---

## 🎯 Requirement Fulfillment

| Requirement | Status | Implementation |
|------------|--------|----------------|
| **Student can reserve borrowed books** | ✅ | `ReservationService::reserveBook()` |
| **Student can cancel own reservation** | ✅ | `ReservationService::cancelReservation()` |
| **Student can view queue position** | ✅ | `my-reservations.blade.php` |
| **Student receives notifications** | ✅ | Observer Pattern + Notifications page |
| **FIFO queue system** | ✅ | Queue position auto-calculated |
| **Auto-notify on book return** | ✅ | Observer Pattern in `return()` method |
| **3-day expiry rule** | ✅ | Scheduled command + `checkAndExpireReservations()` |
| **Staff can view queues** | ✅ | `queue.blade.php` + `all-queues.blade.php` |
| **Observer Pattern** | ✅ | `ReservationSubject` + Observers |
| **Parameterized queries** | ✅ | Laravel Eloquent ORM throughout |
| **Input validation** | ✅ | Whitelist validation in all methods |
| **Transaction-based** | ✅ | All critical operations wrapped in DB::transaction() |

---

## 🧪 Testing the Module

### Manual Testing Steps:

**As Student:**
1. Login as a student
2. Go to Books Catalog
3. Find a borrowed book (status: Borrowed)
4. Click "Reserve This Book"
5. Check "My Reservations" - should show queue position
6. Wait for book to be returned by staff
7. Check "My Notifications" - should show notification when book available
8. Borrow the book within 3 days

**As Staff:**
1. Login as staff/admin
2. Return a borrowed book that has reservations
3. System should auto-notify first person in queue
4. Go to "Reservation Queues"
5. View queue for specific books
6. See all user details and notification status

**Scheduled Task Testing:**
```bash
# Run manually to test expiry logic
php artisan reservations:check-expired
```

---

## 📊 Scenario Walkthrough (from SRS)

**Scenario:** Book A borrowed → Students B, C, D reserve → Book returned → B notified → B doesn't borrow → C notified

**Implementation Flow:**
1. Student B reserves Book A → Queue position: 1, Status: 'waiting'
2. Student C reserves Book A → Queue position: 2, Status: 'waiting'
3. Student D reserves Book A → Queue position: 3, Status: 'waiting'
4. Staff returns Book A:
   ```php
   // In BorrowingController::return()
   $reservationService->notifyNextInQueue($book);
   // → Student B updated to 'notified', expiry set to +3 days
   // → Observer logs notification
   ```
5. After 3 days, Student B doesn't borrow:
   ```bash
   php artisan reservations:check-expired
   # → Student B marked 'expired'
   # → Student C updated to 'notified'
   # → Student C sees notification
   ```
6. Student C sees notification in "My Notifications" page ✅

---

## 🚀 Features Implemented

### Student Features:
- ✅ Reserve unavailable (borrowed) books
- ✅ Cancel own reservations
- ✅ View queue position for each reservation
- ✅ Receive notifications when book available
- ✅ See countdown timer (remaining days to collect)
- ✅ Visual status indicators (Waiting / Notified)
- ✅ Protection against duplicate reservations
- ✅ Max 5 active reservations limit

### Staff Features:
- ✅ View all reservation queues
- ✅ View specific queue for a book
- ✅ See user details (name, email, ID)
- ✅ See notification status and timestamps
- ✅ See expiry countdowns
- ✅ Monitor FIFO queue order

### System Features:
- ✅ Observer Pattern for automatic notifications
- ✅ FIFO queue management
- ✅ Automatic queue re-ordering on cancellation
- ✅ 3-day expiry with auto-notification of next person
- ✅ Transaction-based operations (atomicity)
- ✅ Secure parameterized queries
- ✅ Whitelist input validation
- ✅ Scheduled task for expiry checking

---

## 📝 Key Design Decisions

1. **Separate Status Values:**
   - `waiting` - In queue, book not yet available
   - `notified` - Book available, user notified, 3-day countdown started
   - `fulfilled` - User borrowed the book
   - `cancelled` - User cancelled reservation
   - `expired` - 3-day deadline passed without borrowing

2. **Queue Position Auto-Calculation:**
   - New reservation gets `max(queue_position) + 1`
   - Positions re-ordered automatically on cancellation
   - Ordered by `queue_position`, then `created_at`

3. **Book Status Integration:**
   - Book returned with reservations → Status: 'Reserved'
   - First notified person borrows → Status: 'Borrowed'
   - Notification expires, no more reservations → Status: 'Available'

4. **Security:**
   - All DB operations via Eloquent ORM (auto-parameterized)
   - Integer validation for IDs
   - Authorization checks for ownership
   - Staff middleware for admin routes

---

## 🔧 Maintenance & Administration

### Running the Scheduler:
The scheduler needs to run continuously. Add to cron (Linux) or Task Scheduler (Windows):

**Linux cron:**
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Windows Task Scheduler:**
```cmd
php artisan schedule:run
```
Set to run every minute.

### Manual Commands:
```bash
# Check for expired reservations manually
php artisan reservations:check-expired

# View all console commands
php artisan list
```

### Monitoring:
- Check logs in `storage/logs/laravel.log`
- Search for: "Reservation created", "Next user notified", "Reservation expired"

---

## 📦 Files Created/Modified

### New Files:
```
app/
  Console/Commands/
    CheckExpiredReservations.php
  Http/Controllers/
    ReservationController.php
  Services/
    ReservationService.php

database/migrations/
  2025_12_18_071702_add_notified_at_to_reservations_table.php

resources/views/reservations/
  my-reservations.blade.php
  my-notifications.blade.php
  queue.blade.php
  all-queues.blade.php
```

### Modified Files:
```
app/Models/Reservation.php (enhanced)
app/Http/Controllers/BorrowingController.php (Observer integration)
resources/views/layouts/sidebar.blade.php (menu items)
resources/views/layouts/book-cards.blade.php (Reserve button)
routes/web.php (new routes)
routes/console.php (scheduler)
```

---

## ✨ Conclusion

The Book Reservation Module has been **fully implemented** according to the SRS requirements:

- ✅ Complete separation of Student and Staff responsibilities
- ✅ Observer Pattern for automatic notifications
- ✅ FIFO queue system with auto-management
- ✅ 3-day expiry with automatic progression
- ✅ Secure parameterized queries
- ✅ Input validation (whitelist-based)
- ✅ Transaction-based atomicity
- ✅ Professional UI with countdown timers
- ✅ Scheduled task for automation
- ✅ No modification to other team members' modules (only integrated via existing interfaces)

**The module is production-ready and fully testable.**

---

## 📞 Support

For any issues or questions regarding the reservation module:
1. Check logs: `storage/logs/laravel.log`
2. Test commands manually: `php artisan reservations:check-expired`
3. Verify routes: `php artisan route:list | grep reservation`
4. Check database: Verify `reservations` table schema

---

**Implementation Date:** December 18, 2025  
**Laravel Version:** 11.x  
**PHP Version:** 8.2+
