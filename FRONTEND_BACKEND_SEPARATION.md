# Frontend/Backend Separation Guide

## Overview

This guide explains how to run the Library System with **separate frontend and backend** on different ports to allow external module access via API.

```
┌─────────────────────────┐
│  Frontend (Port 8000)    │  ◄─── User Interface (Blade Templates)
│  - Web Routes           │
│  - HTML Rendering       │
└────────────┬────────────┘
             │ AJAX/Fetch
             │ (API calls to port 8001)
             │
┌────────────▼────────────┐
│  Backend API (Port 8001)│  ◄─── JSON API (For external modules)
│  - API Routes          │
│  - BookApiController   │
└─────────────────────────┘
```

---

## Architecture Changes

### 1. **Configuration Files**

Two `.env` files created:

- **`.env.frontend`**: Points frontend to backend API at `http://localhost:8001`
- **`.env.backend`**: Backend runs at `http://localhost:8001`

### 2. **BookApiClient Updates**

```php
// Before (same machine):
$this->baseUrl = config('app.url') . '/api/books';

// After (separate machines):
$apiUrl = config('app.api_url') ?? config('app.url');
$this->baseUrl = $apiUrl . '/api/books';
```

Now `BookApiClient` reads `app.api_url` from config, allowing:
- **Frontend**: Calls backend at port 8001
- **Other Modules**: Can also call backend at port 8001

### 3. **JavaScript Updates**

Frontend AJAX calls now use `API_URL` from config:

```javascript
// book-table.blade.php
const apiUrl = '{{ config("app.api_url") ?? config("app.url") }}';
fetch(`${apiUrl}/api/books/${bookId}/borrowing-history`)
```

### 4. **CORS Middleware**

Created `app/Http/Middleware/HandleCors.php` to allow:
- Port 8000 (frontend) → Port 8001 (backend) requests
- Cross-origin credentials support
- All HTTP methods (GET, POST, PUT, DELETE, PATCH)

### 5. **BookController Refactoring**

**Before:** Frontend augmented books with borrowing stats
```php
// Direct service call (frontend only)
$book->borrowing_stats = $this->borrowingService->getBookBorrowingStats($book->bookId);
```

**After:** Frontend makes AJAX call to backend
```javascript
// AJAX call (allows external modules to access same endpoint)
fetch(`${apiUrl}/api/books/${bookId}/borrowing-history`)
```

---

## Running Separate Frontend & Backend

### Terminal 1: Frontend Server (Port 8000)

```powershell
# Copy .env.frontend to .env
Copy-Item .env.frontend .env

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run frontend server
php artisan serve --port 8000

# Output: http://127.0.0.1:8000
```

### Terminal 2: Backend API Server (Port 8001)

```powershell
# Copy .env.backend to .env
Copy-Item .env.backend .env

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run backend server
php artisan serve --port 8001

# Output: http://127.0.0.1:8001
```

---

## Workflow

### 1. User navigates to frontend (port 8000)

```
GET http://localhost:8000/books
     ↓
BookController::index()
     ↓
BookService::getFilteredBooks()  ← Direct DB access (same machine)
     ↓
Render books/index.blade.php
     ↓
Return HTML to browser
```

### 2. User clicks borrowing history badge

```
JavaScript AJAX
     ↓
fetch(`http://localhost:8001/api/books/{id}/borrowing-history`)
     ↓ HTTP Request to Backend
Api\BookApiController::getBorrowingHistory()
     ↓
BorrowingService::getBookBorrowingStats()  ← Direct DB access
     ↓
Return JSON response
     ↓
JavaScript renders modal
```

### 3. Other modules access book data

```
External Module (e.g., ReservationService)
     ↓
BookApiClient::getBook($id)
     ↓ HTTP Request
http://localhost:8001/api/books/{id}
     ↓
Api\BookApiController::show()
     ↓
Return JSON response
```

---

## Key Files Modified

| File | Change | Purpose |
|------|--------|---------|
| `.env.frontend` | Created | Frontend config (port 8000, API_URL=8001) |
| `.env.backend` | Created | Backend config (port 8001) |
| `BookApiClient.php` | Updated | Uses `app.api_url` config |
| `book-table.blade.php` | Updated | AJAX calls use `API_URL` |
| `BookController.php` | Refactored | Frontend only, no service augmentation |
| `HandleCors.php` | Created | Cross-origin request handling |
| `AppServiceProvider.php` | Updated | CORS macro registration |

---

## External Module Access

Other modules can now access book data via API:

```php
// ReservationService, BorrowingService, etc.
$bookApiClient = new BookApiClient();
$book = $bookApiClient->getBook($bookId);  // Calls http://localhost:8001/api/books/{id}
```

**Before separation:** BookApiClient called same machine (`http://localhost:8000/api/books`)
**After separation:** BookApiClient calls backend (`http://localhost:8001/api/books`)

---

## Benefits

✅ **Scalability**: Frontend and backend can be deployed independently
✅ **API-First**: External modules use same API as frontend
✅ **Port Conflict Avoidance**: No competing for port 8000
✅ **Clean Separation**: Frontend = Views, Backend = Data
✅ **Future Ready**: Easy to move backend to separate server

---

## Troubleshooting

### CORS Errors in Browser Console

```
Access to XMLHttpRequest at 'http://localhost:8001/...' from origin 
'http://localhost:8000' has been blocked by CORS policy
```

**Solution**: Ensure HandleCors middleware is active and both servers are running.

### API calls return 404

- Check backend is running on port 8001: `http://localhost:8001`
- Verify `API_URL=http://localhost:8001` in frontend `.env`
- Run `php artisan route:clear` on backend

### Frontend/Backend out of sync

Both run from **same codebase**, so code changes require restart on both:

```powershell
# Terminal 1 (frontend): Ctrl+C, then restart
php artisan serve --port 8000

# Terminal 2 (backend): Ctrl+C, then restart
php artisan serve --port 8001
```

---

## Summary

You now have:

1. **Frontend** (Port 8000): Renders views, makes AJAX calls to backend
2. **Backend API** (Port 8001): Provides data via REST endpoints
3. **External Access**: Other modules call backend API via BookApiClient
4. **CORS Support**: Cross-origin requests fully enabled

This architecture enables your system to scale independently while maintaining a clean API-first design! 🎯
