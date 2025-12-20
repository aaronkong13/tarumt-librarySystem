# 🔄 Migration Guide: Remove API Client Files

## Current Situation

You have API client files that are no longer needed:
- `app/Services/BookApiClient.php`
- `app/Services/UserApiClient.php`

**New Approach:** Services make direct HTTP calls to API endpoints instead.

---

## ✅ What Was Updated

### 1. BorrowingService
**File:** `app/Services/BorrowingService.php`

**Changes:**
- ❌ Removed `use App\Services\BookApiClient;`
- ✅ Added `use Illuminate\Support\Facades\Http;`
- ✅ Added helper methods:
  - `getBookFromApi($bookId)` - Calls Book API directly
  - `getUserFromApi($userId)` - Calls User API directly

**New Code:**
```php
protected function getBookFromApi($bookId)
{
    $apiUrl = config('app.api_url', config('app.url'));
    $response = Http::get("{$apiUrl}/api/books/{$bookId}");
    
    if (!$response->successful()) {
        throw new Exception("Failed to fetch book from API");
    }
    
    return $response->json()['data'];
}
```

---

## 🗑️ Files to Delete

### Safe to Delete Now:

```bash
# These files are no longer used
rm app/Services/BookApiClient.php
rm app/Services/UserApiClient.php
```

**Why?**
- Services now use `Http::get()` directly
- API controllers handle the requests
- No need for wrapper classes

---

## 🔍 Check for Remaining Usage

Before deleting, check if any other files still use them:

```bash
# Search for BookApiClient usage
grep -r "BookApiClient" app/Services/
grep -r "BookApiClient" app/Http/Controllers/

# Search for UserApiClient usage
grep -r "UserApiClient" app/Services/
grep -r "UserApiClient" app/Http/Controllers/
```

If you find any usage, update those files to use the same pattern as BorrowingService.

---

## 📝 Pattern to Follow

### Old Pattern (Don't Use):
```php
use App\Services\BookApiClient;

class MyService
{
    protected BookApiClient $bookApiClient;
    
    public function __construct()
    {
        $this->bookApiClient = new BookApiClient();
    }
    
    public function doSomething($bookId)
    {
        $book = $this->bookApiClient->getBook($bookId);
    }
}
```

### New Pattern (Use This):
```php
use Illuminate\Support\Facades\Http;

class MyService
{
    protected function getBookFromApi($bookId)
    {
        $apiUrl = config('app.api_url');
        $response = Http::get("{$apiUrl}/api/books/{$bookId}");
        
        if (!$response->successful()) {
            throw new Exception("Failed to fetch book");
        }
        
        return $response->json()['data'];
    }
    
    public function doSomething($bookId)
    {
        $book = $this->getBookFromApi($bookId);
    }
}
```

---

## 🎯 Module Communication Matrix

| Service | Needs | Method | Code |
|---------|-------|--------|------|
| BorrowingService | Book data | HTTP API | `Http::get('/api/books/123')` |
| BorrowingService | User data | HTTP API | `Http::get('/api/users/456')` |
| ReservationService | Book data | HTTP API | `Http::get('/api/books/123')` |
| ReservationService | User data | HTTP API | `Http::get('/api/users/456')` |
| BookService | Book data | Direct | `Book::find(123)` |
| UserService | User data | Direct | `User::find(456)` |

---

## ✅ Verification Steps

### Step 1: Update All Services
Make sure all services using BookApiClient or UserApiClient are updated.

### Step 2: Test the Application
```bash
# Start both servers
php artisan serve --port=8000 --env=.env.frontend
php artisan serve --port=8001 --env=.env.backend

# Test borrowing functionality
# Make sure cross-module calls work
```

### Step 3: Delete API Client Files
```bash
rm app/Services/BookApiClient.php
rm app/Services/UserApiClient.php
```

### Step 4: Verify No Errors
```bash
# Check for any remaining references
grep -r "BookApiClient" app/
grep -r "UserApiClient" app/
```

---

## 🔄 Complete Example: BorrowingService

```php
<?php

namespace App\Services;

use App\Models\Borrowing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

class BorrowingService
{
    /**
     * Get book from Book API (different module)
     */
    protected function getBookFromApi($bookId)
    {
        $apiUrl = config('app.api_url', config('app.url'));
        $response = Http::get("{$apiUrl}/api/books/{$bookId}");
        
        if (!$response->successful()) {
            throw new Exception("Failed to fetch book from API: " . $response->body());
        }
        
        $data = $response->json();
        if (!$data['success']) {
            throw new Exception($data['message'] ?? 'Failed to fetch book');
        }
        
        return $data['data'];
    }
    
    /**
     * Get user from User API (different module)
     */
    protected function getUserFromApi($userId)
    {
        $apiUrl = config('app.api_url', config('app.url'));
        $response = Http::get("{$apiUrl}/api/users/{$userId}");
        
        if (!$response->successful()) {
            throw new Exception("Failed to fetch user from API: " . $response->body());
        }
        
        $data = $response->json();
        if (!$data['success']) {
            throw new Exception($data['message'] ?? 'Failed to fetch user');
        }
        
        return $data['data'];
    }
    
    /**
     * Borrow a book - uses API calls for cross-module data
     */
    public function borrowBook($userId, $bookId)
    {
        return DB::transaction(function () use ($userId, $bookId) {
            // Get data from other modules via API
            $book = $this->getBookFromApi($bookId);
            $user = $this->getUserFromApi($userId);
            
            // Validate business rules
            if (!$user['is_active']) {
                throw new Exception('User is not active');
            }
            
            if ($book['status'] !== 'Available') {
                throw new Exception('Book is not available');
            }
            
            // Create borrowing record (own module - direct model access)
            $borrowing = Borrowing::create([
                'user_id' => $userId,
                'book_id' => $bookId,
                'borrow_date' => now(),
                'due_date' => now()->addDays(14),
                'status' => 'borrowed',
            ]);
            
            // Update book status via API (different module)
            $this->updateBookStatusViaApi($bookId, 'Borrowed');
            
            return $borrowing;
        });
    }
    
    /**
     * Update book status via API
     */
    protected function updateBookStatusViaApi($bookId, $status)
    {
        $apiUrl = config('app.api_url', config('app.url'));
        $response = Http::put("{$apiUrl}/api/books/{$bookId}", [
            'status' => $status
        ]);
        
        if (!$response->successful()) {
            throw new Exception("Failed to update book status");
        }
        
        return $response->json()['data'];
    }
}
```

---

## Summary

### Before:
```
BorrowingService
    → BookApiClient (wrapper class)
        → Http::get('/api/books/123')
            → BookApiController
```

### After:
```
BorrowingService
    → Http::get('/api/books/123')
        → BookApiController
```

**Result:** Simpler, cleaner, no unnecessary wrapper classes! ✅

---

**Status:** Ready to delete API client files once all services are updated!
