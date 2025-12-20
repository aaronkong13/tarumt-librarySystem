# Module Separation Architecture - Corrected

## ✅ Correct Understanding

### Module Boundaries

```
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│  Book Module    │   │  User Module    │   │ Borrowing Module│
├─────────────────┤   ├─────────────────┤   ├─────────────────┤
│ - BookService   │   │ - UserService   │   │ - BorrowingService│
│ - BookController│   │ - UserController│   │ - BorrowingController│
│ - Book Model    │   │ - User Model    │   │ - Borrowing Model│
└─────────────────┘   └─────────────────┘   └─────────────────┘
```

**These are SEPARATE MODULES** - They must communicate via API calls!

---

## 🏗️ New Architecture (No API Client Files)

### ❌ OLD WAY (Remove These Files):
- `app/Services/BookApiClient.php` → DELETE
- `app/Services/UserApiClient.php` → DELETE

### ✅ NEW WAY (Direct HTTP Calls):

#### BorrowingService (Example)
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BorrowingService
{
    /**
     * Call Book API directly - No BookApiClient needed
     */
    protected function getBookFromApi($bookId)
    {
        $apiUrl = config('app.api_url');
        $response = Http::get("{$apiUrl}/api/books/{$bookId}");
        
        if (!$response->successful()) {
            throw new Exception("Failed to fetch book");
        }
        
        return $response->json()['data'];
    }
    
    /**
     * Call User API directly - No UserApiClient needed
     */
    protected function getUserFromApi($userId)
    {
        $apiUrl = config('app.api_url');
        $response = Http::get("{$apiUrl}/api/users/{$userId}");
        
        if (!$response->successful()) {
            throw new Exception("Failed to fetch user");
        }
        
        return $response->json()['data'];
    }
    
    public function borrowBook($userId, $bookId)
    {
        // Get book data from Book API
        $book = $this->getBookFromApi($bookId);
        
        // Get user data from User API
        $user = $this->getUserFromApi($userId);
        
        // Borrowing logic...
    }
}
```

---

## 📊 Communication Flow

### Cross-Module Communication (Different Modules)

```
┌─────────────────────────────────────────────────────────────┐
│              Borrowing Module needs Book data               │
└─────────────────────────────────────────────────────────────┘
                            ↓
              BorrowingService::borrowBook()
                            ↓
              $book = $this->getBookFromApi($bookId);
                            ↓
              Http::get('http://localhost:8001/api/books/123')
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                  Backend API (Port 8001)                    │
├─────────────────────────────────────────────────────────────┤
│  Route: /api/books/123                                      │
│     ↓                                                       │
│  BookApiController@show(123)                                │
│     ↓                                                       │
│  BookService::getBookById(123)                              │
│     ↓                                                       │
│  Book::find(123) → Database                                 │
│     ↓                                                       │
│  Return JSON: { "success": true, "data": {...} }            │
└─────────────────────────────────────────────────────────────┘
                            ↓
              BorrowingService receives book data
                            ↓
              Continue with borrowing logic
```

---

## 🎯 Module Access Rules

| From Module | To Module | Method | Example |
|------------|-----------|---------|---------|
| **Book** | Book | ✅ Direct | `BookController → BookService` |
| **User** | User | ✅ Direct | `UserController → UserService` |
| **Borrowing** | Borrowing | ✅ Direct | `BorrowingController → BorrowingService` |
| **Borrowing** | Book | 🌐 HTTP API | `Http::get('/api/books/123')` |
| **Borrowing** | User | 🌐 HTTP API | `Http::get('/api/users/456')` |
| **Reservation** | Book | 🌐 HTTP API | `Http::get('/api/books/123')` |
| **Reservation** | User | 🌐 HTTP API | `Http::get('/api/users/456')` |

---

## 🔧 Implementation Steps

### Step 1: Update BorrowingService

```php
// Remove this import
use App\Services\BookApiClient; // ❌ DELETE
use App\Services\UserApiClient; // ❌ DELETE

// Add this import
use Illuminate\Support\Facades\Http; // ✅ ADD

class BorrowingService
{
    // Add helper methods for API calls
    protected function getBookFromApi($bookId) { /* ... */ }
    protected function getUserFromApi($userId) { /* ... */ }
    
    // Use them in your methods
    public function borrowBook($userId, $bookId) {
        $book = $this->getBookFromApi($bookId);
        $user = $this->getUserFromApi($userId);
        // ...
    }
}
```

### Step 2: Update ReservationService (If Needed)

Same pattern as BorrowingService

### Step 3: Delete API Client Files

```bash
rm app/Services/BookApiClient.php
rm app/Services/UserApiClient.php
```

### Step 4: Update Any Other Services

Search for any remaining usage:
```bash
grep -r "BookApiClient" app/
grep -r "UserApiClient" app/
```

---

## 📋 Controllers Architecture

### Frontend Controllers (Port 8000)
**Purpose:** Return HTML views for users

```php
// app/Http/Controllers/BookController.php
class BookController extends Controller
{
    protected BookService $bookService;
    
    public function index()
    {
        $books = $this->bookService->getAllBooks();
        return view('books.index', compact('books'));
    }
}
```

### Backend API Controllers (Port 8001)
**Purpose:** Return JSON for API consumers

```php
// app/Http/Controllers/Api/BookApiController.php
class BookApiController extends Controller
{
    protected BookService $bookService;
    
    public function index()
    {
        $books = $this->bookService->getAllBooks();
        return response()->json(['success' => true, 'data' => $books]);
    }
}
```

### Cross-Module Usage
**Other modules call these API endpoints via HTTP:**

```php
// From BorrowingService
$response = Http::get('http://localhost:8001/api/books');
$books = $response->json()['data'];
```

---

## ✅ Summary

### What to Remove:
- ❌ `app/Services/BookApiClient.php`
- ❌ `app/Services/UserApiClient.php`
- ❌ All `use App\Services\BookApiClient;` imports
- ❌ All `use App\Services\UserApiClient;` imports

### What to Use Instead:
- ✅ `use Illuminate\Support\Facades\Http;`
- ✅ Direct HTTP calls: `Http::get(config('app.api_url') . '/api/books/123')`
- ✅ API Controllers handle the requests
- ✅ Services provide business logic

### Architecture:
```
BorrowingService (Borrowing Module)
    ↓ HTTP Call
    Http::get('/api/books/123')
    ↓
BookApiController (Backend)
    ↓ Direct Call (Same Module)
    BookService
    ↓
    Book Model → Database
```

---

**Status**: ✅ Correct module separation with no API client files needed!
