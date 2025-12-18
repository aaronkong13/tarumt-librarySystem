# Test Data Creation Guide

## 🧪 Setting Up Test Data for Reservation Module

### Prerequisites
- Database migrations completed: `php artisan migrate`
- Have at least one Student user
- Have at least one Staff/Admin user
- Have at least one book in the system

---

## 📝 Step-by-Step Test Setup

### 1. Create Test Users (if needed)

```sql
-- Create a Student user
INSERT INTO users (name, email, password, role, status, created_at, updated_at)
VALUES ('Test Student', 'student@test.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'Active', NOW(), NOW());
-- Password: password

-- Create a Staff user
INSERT INTO users (name, email, password, role, status, created_at, updated_at)
VALUES ('Test Staff', 'staff@test.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff', 'Active', NOW(), NOW());
-- Password: password
```

### 2. Create a Borrowed Book (if needed)

```sql
-- Create or update a book to be borrowed
UPDATE books 
SET status = 'Borrowed' 
WHERE bookId = 1;
```

### 3. Create Test Reservations

```sql
-- Reservation by Student (Queue Position #1)
INSERT INTO reservations (user_id, book_id, reservation_date, status, queue_position, created_at, updated_at)
VALUES (1, 1, NOW(), 'waiting', 1, NOW(), NOW());

-- Another student reserves same book (Queue Position #2)
INSERT INTO reservations (user_id, book_id, reservation_date, status, queue_position, created_at, updated_at)
VALUES (2, 1, NOW(), 'waiting', 2, NOW(), NOW());
```

### 4. Test Notification Status

```sql
-- Simulate a notified reservation (3 days to collect)
INSERT INTO reservations (user_id, book_id, reservation_date, notified_at, expiry_date, status, queue_position, created_at, updated_at)
VALUES (3, 2, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'notified', 1, NOW(), NOW());
```

### 5. Test Expired Notification

```sql
-- Simulate an expired notification (already past deadline)
INSERT INTO reservations (user_id, book_id, reservation_date, notified_at, expiry_date, status, queue_position, created_at, updated_at)
VALUES (4, 3, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'notified', 1, NOW(), NOW());

-- Then run: php artisan reservations:check-expired
-- This should mark it as expired and notify next person
```

---

## 🎬 Complete Test Scenario

### Scenario: Full Reservation Flow

```bash
# 1. Login as Student A
# 2. Reserve a borrowed book via UI
# 3. Check "My Reservations" - should see queue position #1

# 4. Login as another Student B
# 5. Reserve the same book
# 6. Check "My Reservations" - should see queue position #2

# 7. Login as Staff
# 8. Go to "Reservation Queues"
# 9. View queue for the book - should see both students

# 10. Return the book (mark as returned)
# System auto-notifies Student A

# 11. Login as Student A
# 12. Check "My Notifications" - should see notification

# 13. Wait 3 days OR manually expire:
UPDATE reservations SET expiry_date = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = 1;
php artisan reservations:check-expired

# 14. Student B should now be notified
```

---

## 🔧 Useful Test Queries

### Check Current State
```sql
-- View all reservations with user names
SELECT 
    r.id,
    r.queue_position,
    r.status,
    u.name as user_name,
    b.title as book_title,
    r.reservation_date,
    r.notified_at,
    r.expiry_date
FROM reservations r
JOIN users u ON r.user_id = u.id
JOIN books b ON r.book_id = b.bookId
ORDER BY r.book_id, r.queue_position;
```

### Reset Test Data
```sql
-- Clear all reservations
DELETE FROM reservations;

-- Reset book status
UPDATE books SET status = 'Available';
```

### Simulate Time-Based Scenarios
```sql
-- Make a reservation about to expire (1 day left)
UPDATE reservations 
SET notified_at = DATE_SUB(NOW(), INTERVAL 2 DAY),
    expiry_date = DATE_ADD(NOW(), INTERVAL 1 DAY),
    status = 'notified'
WHERE id = 1;

-- Make a reservation already expired
UPDATE reservations 
SET notified_at = DATE_SUB(NOW(), INTERVAL 4 DAY),
    expiry_date = DATE_SUB(NOW(), INTERVAL 1 DAY),
    status = 'notified'
WHERE id = 2;
```

---

## ✅ Verification Checklist

After setting up test data, verify:

- [ ] Students can see their reservations
- [ ] Queue positions are correct (1, 2, 3, ...)
- [ ] Staff can view all queues
- [ ] Notified reservations show in "My Notifications"
- [ ] Countdown timers display correctly
- [ ] Expiry command processes old notifications
- [ ] Observer pattern logs appear in `storage/logs/laravel.log`

---

## 🐛 Debugging Tips

### If reservations don't appear:
```sql
-- Check reservation status
SELECT * FROM reservations WHERE user_id = YOUR_USER_ID;

-- Check if soft deleted
SELECT * FROM reservations WHERE deleted_at IS NOT NULL;
```

### If notifications don't work:
```sql
-- Check notification status
SELECT * FROM reservations WHERE status = 'notified';

-- Check log files
tail -f storage/logs/laravel.log | grep Reservation
```

### If expiry doesn't process:
```bash
# Run manually
php artisan reservations:check-expired

# Check scheduler is running
php artisan schedule:list

# Test specific time
php artisan tinker
# Then in tinker:
App\Services\ReservationService::checkAndExpireReservations();
```

---

## 📊 Sample Data SQL Script

```sql
-- Complete test setup
-- Run this to get a fully populated test environment

-- 3 Students
INSERT INTO users (name, email, password, role, status, created_at, updated_at) VALUES
('Alice Student', 'alice@test.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'Active', NOW(), NOW()),
('Bob Student', 'bob@test.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'Active', NOW(), NOW()),
('Charlie Student', 'charlie@test.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'Active', NOW(), NOW());

-- Make book 1 borrowed
UPDATE books SET status = 'Borrowed' WHERE bookId = 1;

-- Create queue for book 1
INSERT INTO reservations (user_id, book_id, reservation_date, status, queue_position, created_at, updated_at) VALUES
((SELECT id FROM users WHERE email = 'alice@test.com'), 1, NOW(), 'waiting', 1, NOW(), NOW()),
((SELECT id FROM users WHERE email = 'bob@test.com'), 1, NOW(), 'waiting', 2, NOW(), NOW()),
((SELECT id FROM users WHERE email = 'charlie@test.com'), 1, NOW(), 'waiting', 3, NOW(), NOW());
```

---

**Happy Testing! 🎉**
