# Design Patterns Implementation

This document describes the **State Pattern** and **Observer Pattern** implementations for the Library System's book borrowing and reservation functionality.

---

## Table of Contents

1. [State Pattern - Book Borrowing](#state-pattern---book-borrowing)
2. [Observer Pattern - Book Reservation](#observer-pattern---book-reservation)
3. [File Structure](#file-structure)
4. [Usage Examples](#usage-examples)
5. [API Endpoints](#api-endpoints)

---

## State Pattern - Book Borrowing

### Overview

The State Pattern is used to manage the different states a book can be in during the borrowing lifecycle. Instead of using conditional statements to check the book's status, each state is represented by a separate class that handles its own behavior.

### States

| State | Description | Can Borrow | Can Return | Can Renew |
|-------|-------------|------------|------------|-----------|
| **AvailableState** | Book is available for borrowing | ✅ | ❌ | ❌ |
| **BorrowedState** | Book is currently borrowed (not overdue) | ❌ | ✅ | ✅ |
| **OverdueState** | Book is borrowed and overdue | ❌ | ✅ | ❌ |
| **ReturnedState** | Book has been returned | ✅ | ❌ | ❌ |

### State Transitions

```
┌─────────────────┐
│  AvailableState │
└────────┬────────┘
         │ borrow()
         ▼
┌─────────────────┐
│  BorrowedState  │◄──────────────┐
└────────┬────────┘               │
         │                        │ renew()
         ├────────────────────────┘
         │ (if overdue)
         ▼
┌─────────────────┐
│  OverdueState   │
└────────┬────────┘
         │ returnBook()
         ▼
┌─────────────────┐
│  ReturnedState  │
└────────┬────────┘
         │ (book available again)
         ▼
┌─────────────────┐
│  AvailableState │
└─────────────────┘
```

### Class Structure

```
app/
├── Contracts/
│   └── BorrowingStateInterface.php      # Interface for all states
└── States/
    └── Borrowing/
        ├── AvailableState.php           # Book available for borrowing
        ├── BorrowedState.php            # Book currently borrowed
        ├── OverdueState.php             # Book overdue
        ├── ReturnedState.php            # Book returned
        └── BorrowingContext.php         # Context managing state transitions
```

### Interface Definition

```php
interface BorrowingStateInterface
{
    public function borrow(BorrowingContext $context, User $user): ?Borrowing;
    public function returnBook(BorrowingContext $context): void;
    public function renew(BorrowingContext $context): void;
    public function canBorrow(): bool;
    public function canReturn(): bool;
    public function canRenew(): bool;
    public function getStateName(): string;
}
```

### Benefits

1. **Single Responsibility**: Each state class handles only its own behavior
2. **Open/Closed Principle**: New states can be added without modifying existing code
3. **Eliminates Conditionals**: No need for complex if/else or switch statements
4. **Self-Documenting**: State transitions are explicit and traceable

---

## Observer Pattern - Book Reservation

### Overview

The Observer Pattern is used to notify interested parties when a reserved book becomes available. When a book is returned, all observers attached to the reservation subject are automatically notified.

### Components

| Component | Description |
|-----------|-------------|
| **ReservationSubjectInterface** | Interface for subjects that can be observed |
| **ReservationObserverInterface** | Interface for observers that receive notifications |
| **ReservationSubject** | Concrete subject managing book reservation notifications |
| **EmailNotificationObserver** | Observer that sends email notifications |
| **LoggingObserver** | Observer that logs all reservation events |

### Observer Events

| Event | Trigger | Description |
|-------|---------|-------------|
| `onBookAvailable` | Book returned | Notifies next person in reservation queue |
| `onReservationExpiring` | Daily scheduler | Warns users their reservation expires soon |
| `onReservationExpired` | Daily scheduler | Informs users their reservation has expired |
| `onReservationFulfilled` | Book borrowed | Confirms reservation was successfully used |

### Flow Diagram

```
┌──────────────┐     attach()      ┌─────────────────────┐
│   Observer   │◄──────────────────│  ReservationSubject │
│   (Email)    │                   │       (Book)        │
└──────────────┘                   └──────────┬──────────┘
                                              │
┌──────────────┐     attach()                 │
│   Observer   │◄─────────────────────────────┤
│   (Logging)  │                              │
└──────────────┘                              │
                                              │
                   Book Returned ─────────────┤
                                              │
                                              ▼
                                   notifyBookAvailable()
                                              │
            ┌─────────────────────────────────┼─────────────────────────────────┐
            │                                 │                                 │
            ▼                                 ▼                                 ▼
   ┌──────────────────┐             ┌──────────────────┐             ┌──────────────────┐
   │ EmailObserver    │             │ LoggingObserver  │             │ [Future Observer]│
   │ onBookAvailable()│             │ onBookAvailable()│             │ onBookAvailable()│
   └──────────────────┘             └──────────────────┘             └──────────────────┘
```

### Class Structure

```
app/
├── Contracts/
│   ├── ReservationObserverInterface.php  # Observer interface
│   └── ReservationSubjectInterface.php   # Subject interface
└── Observers/
    └── Reservation/
        ├── ReservationSubject.php         # Subject managing observers
        ├── EmailNotificationObserver.php  # Sends email notifications
        └── LoggingObserver.php            # Logs all events
```

### Interface Definitions

```php
interface ReservationObserverInterface
{
    public function onBookAvailable(Book $book, Reservation $reservation): void;
    public function onReservationExpiring(Reservation $reservation): void;
    public function onReservationExpired(Reservation $reservation): void;
    public function onReservationFulfilled(Reservation $reservation): void;
}

interface ReservationSubjectInterface
{
    public function attach(ReservationObserverInterface $observer): void;
    public function detach(ReservationObserverInterface $observer): void;
    public function notifyBookAvailable(): void;
    public function notifyReservationExpiring(): void;
    public function notifyReservationExpired(): void;
    public function notifyReservationFulfilled(Reservation $reservation): void;
}
```

### Benefits

1. **Loose Coupling**: Subject doesn't need to know concrete observer types
2. **Open/Closed Principle**: New observers can be added without modifying existing code
3. **Runtime Flexibility**: Observers can be attached/detached dynamically
4. **Separation of Concerns**: Notification logic is separated from business logic

---

## File Structure

```
app/
├── Contracts/
│   ├── BorrowingStateInterface.php
│   ├── ReservationObserverInterface.php
│   └── ReservationSubjectInterface.php
├── Http/
│   └── Controllers/
│       └── BookStateController.php
├── Observers/
│   └── Reservation/
│       ├── EmailNotificationObserver.php
│       ├── LoggingObserver.php
│       └── ReservationSubject.php
├── Services/
│   └── BookBorrowingStateService.php
└── States/
    └── Borrowing/
        ├── AvailableState.php
        ├── BorrowedState.php
        ├── BorrowingContext.php
        ├── OverdueState.php
        └── ReturnedState.php
```

---

## Usage Examples

### State Pattern Usage

```php
use App\States\Borrowing\BorrowingContext;
use App\Models\Book;

// Create context for a book
$book = Book::find($bookId);
$context = new BorrowingContext($book);

// Check current state
echo $context->getStateName(); // "Available", "Borrowed", "Overdue", etc.

// Check available actions
if ($context->canBorrow()) {
    $context->borrow(); // Transitions to BorrowedState
}

if ($context->canReturn()) {
    $context->returnBook(); // Transitions to ReturnedState
}

if ($context->canRenew()) {
    $context->renew(); // Stays in BorrowedState with extended due date
}
```

### Observer Pattern Usage

```php
use App\Observers\Reservation\ReservationSubject;
use App\Observers\Reservation\EmailNotificationObserver;
use App\Observers\Reservation\LoggingObserver;
use App\Models\Book;

// Create subject for a book
$book = Book::find($bookId);
$subject = new ReservationSubject($book);

// Attach observers
$subject->attach(new EmailNotificationObserver());
$subject->attach(new LoggingObserver());

// When book becomes available (e.g., returned)
$subject->notifyBookAvailable(); // All observers notified

// Can also notify about expiring reservations
$subject->notifyReservationExpiring();
```

### Combined Usage in Service

```php
use App\Services\BookBorrowingStateService;

$service = new BookBorrowingStateService();

// Borrow using State Pattern
$borrowing = $service->borrowBook($userId, $bookId);

// Return using State Pattern + Observer Pattern notifications
$borrowing = $service->returnBook($borrowingId);

// Reserve with Observer Pattern
$reservation = $service->reserveBook($userId, $bookId);

// Get current state
$stateInfo = $service->getBookState($bookId);
// Returns: ['state' => 'Available', 'can_borrow' => true, ...]
```

---

## API Endpoints

### State Pattern Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/state-borrowings/book/{book}/state` | Get book's current state |
| POST | `/state-borrowings/borrow` | Borrow using state pattern |
| POST | `/state-borrowings/{borrowing}/return` | Return using state pattern |
| POST | `/state-borrowings/{borrowing}/renew` | Renew using state pattern |

### Observer Pattern Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/state-borrowings/reserve` | Reserve with observer subscription |
| DELETE | `/state-borrowings/reservations/{id}` | Cancel reservation |
| POST | `/state-borrowings/reservations/process-expired` | Process expired (scheduler) |
| POST | `/state-borrowings/reservations/notify-expiring` | Send expiring notifications |

### Example API Responses

**Get Book State:**
```json
{
    "success": true,
    "data": {
        "book_id": 1,
        "state": "Available",
        "can_borrow": true,
        "can_return": false,
        "can_renew": false
    },
    "pattern": "State Pattern"
}
```

**Borrow Book:**
```json
{
    "success": true,
    "message": "Book borrowed successfully (State Pattern)",
    "data": {
        "id": 1,
        "user_id": 5,
        "book_id": 1,
        "borrow_date": "2025-01-15",
        "due_date": "2025-01-29",
        "status": "borrowed"
    },
    "pattern": "State Pattern - Transition from Available to Borrowed"
}
```

**Return Book:**
```json
{
    "success": true,
    "message": "Book returned successfully (State Pattern)!",
    "data": {
        "id": 1,
        "return_date": "2025-01-20",
        "status": "returned"
    },
    "pattern": "State Pattern (state transition) + Observer Pattern (notification sent)"
}
```

---

## Adding New States

To add a new state (e.g., `LostState`):

1. Create the state class:
```php
// app/States/Borrowing/LostState.php
class LostState implements BorrowingStateInterface
{
    public function canBorrow(): bool { return false; }
    public function canReturn(): bool { return false; }
    public function canRenew(): bool { return false; }
    public function getStateName(): string { return 'Lost'; }
    // ... implement other methods
}
```

2. Update `BorrowingContext` to handle the new state.

## Adding New Observers

To add a new observer (e.g., `SMSNotificationObserver`):

1. Create the observer class:
```php
// app/Observers/Reservation/SMSNotificationObserver.php
class SMSNotificationObserver implements ReservationObserverInterface
{
    public function onBookAvailable(Book $book, Reservation $reservation): void
    {
        // Send SMS to $reservation->user
    }
    // ... implement other methods
}
```

2. Attach it in `BookBorrowingStateService`:
```php
$subject->attach(new SMSNotificationObserver());
```

---

## Logging

Both patterns include comprehensive logging:

- **State Pattern**: Logs state transitions with book_id, current state, and new state
- **Observer Pattern**: Logs all notification events with reservation details

View logs at: `storage/logs/laravel.log`

Example log entries:
```
[2025-01-15 10:30:00] local.INFO: State Pattern: Book borrowed successfully {"borrowing_id":1,"user_id":5,"book_id":1,"new_state":"Borrowed"}
[2025-01-15 10:30:00] local.INFO: Observer Pattern: Reservation created {"reservation_id":1,"observers_attached":2}
```
