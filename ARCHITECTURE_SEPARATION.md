# Frontend-Backend Separation Architecture

## Overview

This document explains the **complete separation** between Frontend (port 8000) and Backend (port 8001) servers.

---

## 🎯 Key Principles

### 1. **Port Separation**
- **Frontend (8000)**: Serves web pages, views, and user interfaces
- **Backend (8001)**: Serves API endpoints and data processing

### 2. **Route Separation**
- **Frontend Routes** (`web.php`): `/books`, `/users`, `/borrowings`, etc.
- **Backend Routes** (`api.php`): `/api/books`, `/api/users`, `/api/borrowings`, etc.

### 3. **Access Restriction**
- ✅ **ALLOWED**: `http://localhost:8000/books` (frontend web pages)
- ✅ **ALLOWED**: `http://localhost:8001/api/books` (backend API)
- ❌ **BLOCKED**: `http://localhost:8000/api/*` (API routes on frontend)

---

## 📁 Environment Configuration

### `.env` (Default - Frontend Mode)
```env
APP_NAME=Laravel
APP_URL=http://localhost:8000
API_URL=http://localhost:8001  # Points to backend
```

### `.env.frontend` (Explicit Frontend)
```env
APP_NAME="LibrarySystem-Frontend"
APP_URL=http://localhost:8000
API_URL=http://localhost:8001  # Backend API endpoint
FRONTEND_MODE=true
```

### `.env.backend` (Explicit Backend)
```env
APP_NAME="LibrarySystem-Backend"
APP_URL=http://localhost:8001
API_URL=http://localhost:8001  # Self-reference
FRONTEND_MODE=false
```

---

## 🚀 Running the Servers

### Option 1: Using Specific .env Files (Recommended)
```bash
# Terminal 1 - Frontend Server
php artisan serve --port=8000 --env=.env.frontend

# Terminal 2 - Backend Server
php artisan serve --port=8001 --env=.env.backend
```

### Option 2: Using Default .env
```bash
# Terminal 1 - Frontend (default .env points to 8000)
php artisan serve --port=8000

# Terminal 2 - Backend (manually specify backend env)
php artisan serve --port=8001 --env=.env.backend
```

---

## 🛡️ Security: API Route Blocking

### Middleware: `BlockApiRoutesOnFrontend`

**Location**: `app/Http/Middleware/BlockApiRoutesOnFrontend.php`

**Purpose**: Prevents accessing `/api/*` routes on the frontend server (port 8000)

**How it Works**:
1. Detects if running on frontend (port 8000)
2. Blocks any request to `/api/*`
3. Returns 403 error with helpful message

**Registered in**: `bootstrap/app.php`

```php
$middleware->append(\App\Http\Middleware\BlockApiRoutesOnFrontend::class);
```

---

## 🏗️ Module Communication Architecture

### Principle: Direct Access vs API Calls

#### ✅ Same Module = Direct Service Calls (No HTTP)
When accessing data **within the same module**, use **direct service calls**:

```php
// ❌ OLD WAY (Unnecessary HTTP call)
class BorrowingService {
    protected BookApiClient $bookApiClient;
    
    public function borrowBook($userId, $bookId) {
        $book = $this->bookApiClient->getBook($bookId); // HTTP call!
    }
}

// ✅ NEW WAY (Direct access - same module)
class BorrowingService {
    protected BookService $bookService;
    
    public function borrowBook($userId, $bookId) {
        $book = $this->bookService->getBookById($bookId); // Direct call!
    }
}
```

#### 🌐 Different Module = API Calls (HTTP)
When accessing data from a **different module**, use **API calls**:

```php
// Example: Borrowing Module needs User data
class BorrowingService {
    protected UserApiClient $userApiClient;
    
    public function borrowBook($userId, $bookId) {
        // This is OK - different module (User module)
        $user = $this->userApiClient->getUser($userId); // HTTP to backend
    }
}
```

---

## 📊 Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     FRONTEND (Port 8000)                    │
├─────────────────────────────────────────────────────────────┤
│  Routes: /books, /users, /borrowings                        │
│  Purpose: Web pages, views, user interface                  │
│  Middleware: BlockApiRoutesOnFrontend                       │
│                                                             │
│  ✅ Accessible: http://localhost:8000/books                │
│  ❌ Blocked:    http://localhost:8000/api/books            │
└─────────────────────────────────────────────────────────────┘
                           │
                           │ Cross-module API calls
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                     BACKEND (Port 8001)                     │
├─────────────────────────────────────────────────────────────┤
│  Routes: /api/books, /api/users, /api/borrowings           │
│  Purpose: API endpoints, data processing                    │
│                                                             │
│  ✅ Accessible: http://localhost:8001/api/books            │
│  ✅ Accessible: http://localhost:8001/api/users            │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Service Communication Examples

### Example 1: Book Module (Same Module)

```php
// BookController (Frontend)
public function show($id) {
    $book = $this->bookService->getBookById($id); // Direct call
    return view('books.show', compact('book'));
}

// BookApiController (Backend)
public function show($id) {
    $book = $this->bookService->getBookById($id); // Direct call
    return response()->json(['success' => true, 'data' => $book]);
}
```

### Example 2: Borrowing Module (Different Modules)

```php
// BorrowingService
class BorrowingService {
    protected BookService $bookService;    // Same module
    protected UserApiClient $userApiClient; // Different module
    
    public function borrowBook($userId, $bookId) {
        // ✅ Direct access - Book is same module
        $book = $this->bookService->getBookById($bookId);
        
        // ✅ API call - User is different module
        $user = $this->userApiClient->getUser($userId);
        
        // Business logic...
    }
}
```

---

## 📋 Controller Naming Convention

### Frontend Controllers (Web Routes)
- **Location**: `app/Http/Controllers/`
- **Purpose**: Handle web requests, return views
- **Examples**: `BookController`, `UserController`, `BorrowingController`

### Backend Controllers (API Routes)
- **Location**: `app/Http/Controllers/Api/`
- **Purpose**: Handle API requests, return JSON
- **Examples**: `BookApiController`, `UserApiController`, `BorrowingApiController`

---

## 🗂️ File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BookController.php          # Frontend (views)
│   │   ├── UserController.php          # Frontend (views)
│   │   ├── BorrowingController.php     # Frontend (views)
│   │   └── Api/
│   │       ├── BookApiController.php    # Backend (JSON)
│   │       ├── UserApiController.php    # Backend (JSON)
│   │       └── BorrowingApiController.php # Backend (JSON)
│   └── Middleware/
│       └── BlockApiRoutesOnFrontend.php # Security
├── Services/
│   ├── BookService.php                  # Book business logic
│   ├── UserService.php                  # User business logic
│   ├── BorrowingService.php             # Borrowing business logic
│   ├── BookApiClient.php                # ❌ REMOVED (use BookService)
│   └── UserApiClient.php                # ✅ KEEP (different module)
└── Models/
    ├── Book.php
    ├── User.php
    └── Borrowing.php

routes/
├── web.php                              # Frontend routes
└── api.php                              # Backend routes
```

---

## ✅ What Was Changed

### 1. **Environment Files**
- ✅ Created `.env.frontend` (port 8000)
- ✅ Created `.env.backend` (port 8001)
- ✅ Updated `.env` with clear comments

### 2. **Middleware**
- ✅ Created `BlockApiRoutesOnFrontend` middleware
- ✅ Registered in `bootstrap/app.php`
- ✅ Blocks `/api/*` access on port 8000

### 3. **Service Layer**
- ✅ Removed `BookApiClient` usage from `BorrowingService`
- ✅ Now uses `BookService` directly (same module)
- ✅ Kept `UserApiClient` for cross-module communication

### 4. **Documentation**
- ✅ This comprehensive guide

---

## 🧪 Testing the Separation

### Test 1: Frontend Web Routes (Should Work)
```bash
curl http://localhost:8000/books
curl http://localhost:8000/users
curl http://localhost:8000/borrowings
```
✅ **Expected**: HTML pages

### Test 2: Backend API Routes (Should Work)
```bash
curl http://localhost:8001/api/books
curl http://localhost:8001/api/users
curl http://localhost:8001/api/borrowings
```
✅ **Expected**: JSON responses

### Test 3: Frontend API Routes (Should Be Blocked)
```bash
curl http://localhost:8000/api/books
curl http://localhost:8000/api/users
```
❌ **Expected**: 403 Forbidden with error message

---

## 📝 Best Practices

### DO ✅
1. Use frontend server (8000) for web pages
2. Use backend server (8001) for API endpoints
3. Use direct service calls within the same module
4. Use API clients for cross-module communication
5. Keep controllers thin, put logic in services

### DON'T ❌
1. Don't access `/api/*` routes on port 8000
2. Don't make HTTP calls within the same module
3. Don't put business logic in controllers
4. Don't bypass the middleware
5. Don't hardcode URLs (use `config('app.api_url')`)

---

## 🔧 Troubleshooting

### Problem: "API routes are not accessible on frontend server"
**Solution**: You're trying to access `/api/*` on port 8000. Use port 8001 instead.

### Problem: "BookApiClient not found"
**Solution**: It was removed. Use `BookService` directly for same-module access.

### Problem: "Which .env file is being used?"
**Solution**: Check the terminal output or use:
```bash
php artisan config:show app.name
```

---

## 🎯 Summary

| Aspect | Frontend (8000) | Backend (8001) |
|--------|----------------|----------------|
| **Purpose** | Web UI | API Endpoints |
| **Routes** | `/books` | `/api/books` |
| **Controllers** | `BookController` | `BookApiController` |
| **Returns** | HTML Views | JSON Data |
| **API Access** | ❌ Blocked | ✅ Allowed |
| **.env** | `.env.frontend` | `.env.backend` |

---

**Architecture Status**: ✅ **Fully Separated**

**Last Updated**: December 20, 2025
