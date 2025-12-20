# Frontend-Backend Separation: Visual Guide

## 🎯 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                          USER'S BROWSER                             │
└─────────────────────────────────────────────────────────────────────┘
                     │                              │
                     │ HTML Pages                   │ AJAX/API Calls
                     ↓                              ↓
┌──────────────────────────────┐    ┌──────────────────────────────┐
│   FRONTEND SERVER (8000)     │    │   BACKEND SERVER (8001)      │
├──────────────────────────────┤    ├──────────────────────────────┤
│  Routes: /books, /users      │    │  Routes: /api/books, /api/users │
│  Returns: HTML Views         │    │  Returns: JSON Data          │
│  .env.frontend              │    │  .env.backend               │
├──────────────────────────────┤    ├──────────────────────────────┤
│  ✅ /books                   │    │  ✅ /api/books               │
│  ✅ /users                   │    │  ✅ /api/users               │
│  ✅ /borrowings              │    │  ✅ /api/borrowings          │
│  ❌ /api/* (BLOCKED)         │    │  ✅ /api/* (ALLOWED)         │
└──────────────────────────────┘    └──────────────────────────────┘
```

---

## 🔒 Middleware Protection

```
Request: http://localhost:8000/api/books
                    ↓
    ┌───────────────────────────────┐
    │ BlockApiRoutesOnFrontend      │
    │ Middleware                    │
    ├───────────────────────────────┤
    │ if (port == 8000 &&           │
    │     path.startsWith('/api'))  │
    │   ↓                           │
    │   BLOCK ❌                     │
    └───────────────────────────────┘
                    ↓
    Response: 403 Forbidden
    {
      "error": "API routes not accessible on frontend",
      "message": "Use http://localhost:8001/api/books instead"
    }
```

---

## 🏢 Module Communication Flow

### Scenario 1: Same Module Access (Direct)

```
User visits: http://localhost:8000/books/123
                    ↓
┌─────────────────────────────────────┐
│    Frontend (Port 8000)             │
├─────────────────────────────────────┤
│  BookController                     │
│     ↓ Direct call                   │
│  BookService                        │
│     ↓ Direct query                  │
│  Book Model                         │
│     ↓                               │
│  Database                           │
└─────────────────────────────────────┘
                    ↓
    Returns HTML View
```

### Scenario 2: Cross-Module Access (API)

```
User borrows book: http://localhost:8000/borrowings/borrow
                         ↓
┌──────────────────────────────────────┐
│    Frontend (Port 8000)              │
├──────────────────────────────────────┤
│  BorrowingController                 │
│     ↓                                │
│  BorrowingService                    │
│     ├─ BookService (Same module)    │ ✅ Direct
│     │                                │
│     └─ UserApiClient (Diff module)  │ 🌐 HTTP Call
│              ↓ HTTP Request          │
│     http://localhost:8001/api/users/1│
└──────────────────────────────────────┘
                    ↓
┌──────────────────────────────────────┐
│    Backend (Port 8001)               │
├──────────────────────────────────────┤
│  UserApiController                   │
│     ↓                                │
│  UserService                         │
│     ↓                                │
│  User Model → Database               │
└──────────────────────────────────────┘
                    ↓
         Returns JSON: { user data }
```

---

## 📊 Request Flow Comparison

### ✅ CORRECT Flow

```
Frontend Web Page:
http://localhost:8000/books
    ↓
BookController → BookService → Database → HTML View

Backend API:
http://localhost:8001/api/books
    ↓
BookApiController → BookService → Database → JSON Response
```

### ❌ BLOCKED Flow

```
Frontend trying to access API:
http://localhost:8000/api/books
    ↓
BlockApiRoutesOnFrontend Middleware
    ↓
403 Forbidden ❌
```

---

## 🗂️ File Structure Map

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── BookController.php           ← Frontend (HTML)
│   │   ├── UserController.php           ← Frontend (HTML)
│   │   ├── BorrowingController.php      ← Frontend (HTML)
│   │   │
│   │   └── Api/                         ← Backend (JSON)
│   │       ├── BookApiController.php    
│   │       ├── UserApiController.php    
│   │       └── BorrowingApiController.php
│   │
│   └── Middleware/
│       └── BlockApiRoutesOnFrontend.php ← Security
│
├── Services/
│   ├── BookService.php                  ← Business logic
│   ├── UserService.php                  ← Business logic
│   ├── BorrowingService.php             ← Business logic
│   ├── BookApiClient.php                ← ❌ REMOVED (not needed)
│   └── UserApiClient.php                ← ✅ KEEP (cross-module)
│
└── Models/
    ├── Book.php
    ├── User.php
    └── Borrowing.php

routes/
├── web.php        ← Frontend routes (/books, /users)
└── api.php        ← Backend routes (/api/books, /api/users)

.env               ← Default (frontend mode)
.env.frontend      ← Explicit frontend config
.env.backend       ← Explicit backend config
```

---

## 🎮 Service Layer Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SERVICE LAYER                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  BookService (Book Module)                                  │
│  ├─ getBookById()      ← Used by BookController            │
│  ├─ createBook()       ← Used by BookController            │
│  └─ updateBook()       ← Used by BookApiController         │
│                           ↑ Direct access (same module)     │
│  ───────────────────────────────────────────────────────    │
│                                                             │
│  BorrowingService (Borrowing Module)                        │
│  ├─ borrowBook()                                            │
│  │   ├─ BookService ✅ Direct (same app)                   │
│  │   └─ UserApiClient 🌐 HTTP (different module)           │
│  │                                                          │
│  └─ returnBook()                                            │
│      └─ BookService ✅ Direct                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Data Flow Examples

### Example 1: View Book Details (Frontend)

```
Step 1: User clicks on book
    URL: http://localhost:8000/books/123
    
Step 2: Frontend routing
    routes/web.php → Route::get('/books/{id}')
    
Step 3: Controller
    BookController@show($id)
    ↓
    $book = $this->bookService->getBookById($id);  ← Direct call
    
Step 4: Service
    BookService@getBookById($id)
    ↓
    Book::find($id)  ← Eloquent query
    
Step 5: Response
    return view('books.show', compact('book'));  ← HTML view
```

### Example 2: Get Book via API (Backend)

```
Step 1: AJAX request
    URL: http://localhost:8001/api/books/123
    
Step 2: Backend routing
    routes/api.php → Route::get('/api/books/{id}')
    
Step 3: API Controller
    BookApiController@show($id)
    ↓
    $book = $this->bookService->getBookById($id);  ← Direct call
    
Step 4: Service (Same as above)
    BookService@getBookById($id)
    ↓
    Book::find($id)
    
Step 5: Response
    return response()->json(['success' => true, 'data' => $book]);
```

---

## 🚫 What's Blocked

```
╔═══════════════════════════════════════════════════════════╗
║              BLOCKED REQUESTS (Port 8000)                 ║
╠═══════════════════════════════════════════════════════════╣
║  ❌ http://localhost:8000/api/books                       ║
║  ❌ http://localhost:8000/api/users                       ║
║  ❌ http://localhost:8000/api/borrowings                  ║
║  ❌ http://localhost:8000/api/*                           ║
╚═══════════════════════════════════════════════════════════╝

Middleware: BlockApiRoutesOnFrontend
Response: 403 Forbidden
Message: "Please use http://localhost:8001/api/* instead"
```

---

## ✅ What's Allowed

```
╔═══════════════════════════════════════════════════════════╗
║           FRONTEND (Port 8000) - Web Routes               ║
╠═══════════════════════════════════════════════════════════╣
║  ✅ http://localhost:8000/books                           ║
║  ✅ http://localhost:8000/users                           ║
║  ✅ http://localhost:8000/borrowings                      ║
║  ✅ http://localhost:8000/dashboard                       ║
╚═══════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════╗
║            BACKEND (Port 8001) - API Routes               ║
╠═══════════════════════════════════════════════════════════╣
║  ✅ http://localhost:8001/api/books                       ║
║  ✅ http://localhost:8001/api/users                       ║
║  ✅ http://localhost:8001/api/borrowings                  ║
║  ✅ http://localhost:8001/api/*                           ║
╚═══════════════════════════════════════════════════════════╝
```

---

## 🎯 Quick Reference

| Aspect | Frontend (8000) | Backend (8001) |
|--------|-----------------|----------------|
| **URL Pattern** | `/books` | `/api/books` |
| **Response Type** | HTML | JSON |
| **Controllers** | `BookController` | `BookApiController` |
| **Routes File** | `web.php` | `api.php` |
| **Purpose** | User Interface | Data API |
| **Environment** | `.env.frontend` | `.env.backend` |
| **API Access** | ❌ Blocked | ✅ Allowed |
| **Service Access** | ✅ Direct | ✅ Direct |

---

## 📝 Summary

1. **Two Separate Servers**: Frontend (8000) + Backend (8001)
2. **Middleware Protection**: Blocks `/api/*` on port 8000
3. **Direct Service Calls**: No HTTP overhead within same app
4. **Clear Separation**: Web routes vs API routes
5. **Environment Files**: Explicit configuration for each server

**Status**: ✅ **Fully Implemented and Documented**

---

Generated: December 20, 2025
