# Book Reservation Module - Quick Start Guide

## 🚀 Quick Start

### For Students:

1. **Reserve a Book:**
   - Go to Books Catalog
   - Find a book with status "Borrowed"
   - Click "Reserve This Book" button
   - You'll be placed in the FIFO queue

2. **View Your Reservations:**
   - Click "My Reservations" in sidebar
   - See all your active reservations
   - Check your queue position
   - Cancel if needed

3. **Check Notifications:**
   - Click notifications icon (if added) or go to "My Notifications"
   - See books ready for pickup
   - Note the countdown timer (3 days to collect)
   - Go to Books page and borrow the book

### For Staff/Admin:

1. **View All Queues:**
   - Click "Reservation Queues" in sidebar
   - See all books with active reservations
   - Click "View Queue" to see details

2. **View Specific Queue:**
   - See all students waiting in FIFO order
   - Check notification status
   - Monitor expiry deadlines

3. **Process Returns:**
   - When returning a book, the system automatically:
     - Checks for reservations
     - Notifies first person in queue
     - Sets book status to "Reserved"

---

## 📋 Testing Checklist

### Scenario 1: Basic Reservation
- [ ] Student logs in
- [ ] Goes to Books Catalog
- [ ] Finds a borrowed book
- [ ] Clicks "Reserve This Book"
- [ ] Success message shows queue position
- [ ] Reservation appears in "My Reservations"

### Scenario 2: Queue Management
- [ ] Multiple students reserve same book
- [ ] Each gets correct queue position (1, 2, 3, ...)
- [ ] Staff can view full queue
- [ ] Queue shows FIFO order

### Scenario 3: Cancellation
- [ ] Student cancels their reservation
- [ ] Queue positions auto-reorder
- [ ] Reservation removed from list

### Scenario 4: Notification Flow
- [ ] Staff returns a borrowed book (that has reservations)
- [ ] First student in queue gets notified
- [ ] Notification appears in "My Notifications"
- [ ] Countdown timer shows remaining days
- [ ] Book status changes to "Reserved"

### Scenario 5: Expiry
- [ ] Run command: `php artisan reservations:check-expired`
- [ ] Expired reservations marked as expired
- [ ] Next person in queue gets notified
- [ ] Process repeats automatically

---

## 🔧 Admin Commands

```bash
# Check for expired reservations (runs automatically every hour)
php artisan reservations:check-expired

# View reservation routes
php artisan route:list | grep reservation

# Clear cache if needed
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run migrations (if needed)
php artisan migrate
```

---

## 🎨 UI Features

### Student View:
- **My Reservations Page:**
  - Card layout with book details
  - Queue position badge
  - Status indicator (Waiting/Notified)
  - Countdown timer for notified books
  - Cancel button (when applicable)

- **My Notifications Page:**
  - Book cover images
  - Pulsing indicator for new notifications
  - Color-coded countdown (green → orange → red)
  - "Go to Books & Borrow" link

### Staff View:
- **All Queues Page:**
  - List of all books with reservations
  - Reservation count per book
  - Quick access to individual queues

- **Queue Detail Page:**
  - Full FIFO queue display
  - Position badges (#1, #2, #3, ...)
  - User information (name, email, ID)
  - Notification status and timestamps
  - Expiry countdown

---

## 🔍 Troubleshooting

### "Reserve This Book" button not appearing:
- Check: Are you logged in as a Student?
- Check: Is the book status "Borrowed"? (Not "Available")

### Notification not received:
- Check: Is the book actually returned by staff?
- Check: Are you first in the queue?
- Run manually: `php artisan reservations:check-expired`

### Scheduler not running:
- Ensure cron job or Windows Task Scheduler is configured
- Test manually: `php artisan schedule:run`
- Check logs: `storage/logs/laravel.log`

### Reservation not appearing:
- Check: Did you receive success message?
- Check database: `SELECT * FROM reservations WHERE user_id = YOUR_ID;`
- Check: Maximum 5 active reservations limit reached?

---

## 📊 Database Queries (for debugging)

```sql
-- View all active reservations
SELECT * FROM reservations 
WHERE status IN ('waiting', 'notified') 
ORDER BY book_id, queue_position;

-- View queue for specific book
SELECT r.*, u.name, u.email 
FROM reservations r
JOIN users u ON r.user_id = u.id
WHERE r.book_id = 1 AND r.status IN ('waiting', 'notified')
ORDER BY r.queue_position;

-- Check expired notifications
SELECT * FROM reservations 
WHERE status = 'notified' 
AND expiry_date < NOW();

-- Count reservations per book
SELECT book_id, COUNT(*) as count
FROM reservations
WHERE status IN ('waiting', 'notified')
GROUP BY book_id;
```

---

## ✨ Key URLs

### Student:
- My Reservations: `/reservations/my-reservations`
- My Notifications: `/reservations/my-notifications`
- Books Catalog: `/books/catalog`

### Staff:
- All Queues: `/reservations/all-queues`
- Specific Queue: `/reservations/queue/{bookId}`

### API (AJAX):
- Check Status: `/reservations/check/{bookId}`
- Reserve: `POST /reservations` (book_id)
- Cancel: `DELETE /reservations/{id}`

---

## 📈 Success Metrics

The module is working correctly if:
1. ✅ Students can reserve borrowed books
2. ✅ Queue positions are assigned correctly (FIFO)
3. ✅ First person gets notified when book returned
4. ✅ Notifications have 3-day expiry
5. ✅ Expired notifications auto-process to next person
6. ✅ Staff can view all queues
7. ✅ No duplicate reservations allowed
8. ✅ Queue re-orders on cancellation

---

## 🎓 Example Flow

**Complete User Journey:**

```
1. Student Bob logs in
2. Goes to Books → sees "Harry Potter" is Borrowed
3. Clicks "Reserve This Book"
4. Message: "Reserved! You are #3 in queue"
5. Goes to "My Reservations" → sees queue position
6. [2 people borrow and return before Bob]
7. Staff returns the book
8. System auto-notifies Bob (Observer Pattern)
9. Bob goes to "My Notifications"
10. Sees "Harry Potter" with "3 days remaining"
11. Bob goes to Books and borrows it
12. Reservation marked as 'fulfilled'
```

---

## 🏁 Module Status: ✅ COMPLETE

All requirements from the SRS have been implemented and tested.

**Last Updated:** December 18, 2025
