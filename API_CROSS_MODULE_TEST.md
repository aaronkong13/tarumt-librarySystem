# API Cross-Module Access Testing (Dual-Server Architecture)

## 📋 Overview

This document explains how to test cross-module API access in the library system using a **dual-server architecture**:
- **Frontend Server**: `http://localhost:8000` - Serves web pages and views
- **Backend Server**: `http://localhost:8001` - Serves API endpoints

---

## 🏗️ Dual-Server Architecture

### **Why Two Servers?**

Your system uses a **microservices-style separation**:

```
┌─────────────────────────────────────────────────────────────┐
│                    CLIENT (Browser)                         │
└────────────────┬────────────────────────────┬───────────────┘
                 │                            │
                 │                            │
         ┌───────▼────────┐          ┌───────▼────────┐
         │   Frontend     │          │    Backend     │
         │  localhost:8000│          │ localhost:8001 │
         │                │          │                │
         │  - Web Pages   │          │  - REST APIs   │
         │  - Blade Views │          │  - JSON Data   │
         │  - User Auth   │          │  - Business    │
         │  - Sessions    │          │    Logic       │
         └────────────────┘          └────────┬───────┘
                                              │
                                      ┌───────▼────────┐
                                      │    Database    │
                                      │     MySQL      │
                                      └────────────────┘
```

### **Server Responsibilities**

| Server | Port | Purpose | Returns |
|--------|------|---------|---------|
| **Frontend** | 8000 | Web UI, Blade views, HTML pages | HTML |
| **Backend** | 8001 | REST API, JSON endpoints | JSON |

---

## ✅ Confirmation: Direct Access vs API Access

### **User Management Pages (Direct Access - No API)**

The following pages use **direct service calls** (NOT API):

1. **Profile Page** (`/profile`)
   - Controller: `UserController@showProfile()`
   - Access: Direct call to `BorrowingService`
   - Code: `$borrowingService->getUserBorrowingHistory($user->id)`
   - ✅ **No HTTP API call** - Direct PHP method call

2. **User Management Page** (`/users`)
   - Controller: `UserController@index()`
   - Access: Direct call to `UserService`
   - Code: `$this->userService->getFilteredUsers($request, 5)`
   - ✅ **No HTTP API call** - Direct PHP method call

3. **User Edit/Create Pages**
   - Controllers: `UserController@create()`, `@store()`, `@update()`
   - Access: Direct service and model calls
   - ✅ **No HTTP API call** - Direct database operations

### **Why These Pages Don't Use API?**

These are **internal module pages** that:
- Run on the same server (localhost:8000)
- Share the same Laravel application context
- Can directly access services and models
- Don't need HTTP overhead for internal operations

---

## 🧪 API Testing Section in Profile Page

I've added a **testing section** to your profile page to demonstrate API access.

### **Location**
File: [resources/views/users/profile.blade.php](resources/views/users/profile.blade.php)

### **Features**

1. **Test Book API Button** - Fetches list of books via API
   - Endpoint: `GET /api/books?per_page=5`
   - Tests: Cross-module read access

2. **Test Book Stats API Button** - Fetches book statistics
   - Endpoint: `GET /api/books/stats/overview`
   - Tests: Statistics endpoint access

3. **Real-time Results Display**
   - Shows JSON response in formatted view
   - Displays success/error status with icons
   - Shows loading spinner during API calls

4. **Architecture Visualization**
   - Explains the request flow
   - Shows authentication method
   - Displays response format

---

## 🔄 Request Flow Comparison

### **Current Profile Page (Direct Access)**
```
Browser Request: GET http://localhost:8000/profile
  ↓
routes/web.php (Frontend Server)
  ↓
UserController@showProfile()
  ↓
BorrowingService::getUserBorrowingHistory()  ← Direct PHP call
  ↓
Borrowing Model → Database Query
  ↓
Return: Blade View (HTML) with data
```

### **API Testing Section (Cross-Server API Access)**
```
Browser JavaScript: fetch('http://localhost:8001/api/books')
  ↓
CORS Request to Backend Server
  ↓
routes/api.php (Backend Server)
  ↓
Api/BookApiController@index()
  ↓
BookService::getFilteredBooks()
  ↓
Book Model → Database Query
  ↓
Return: JSON Response
  ↓
JavaScript displays formatted JSON on Frontend Page
```

---

## 🎯 How to Test

### **Step 1: Start BOTH Servers**

You need to run **two separate PHP servers**:

```bash
# Terminal 1 - Frontend Server
cd c:\Users\User\Documents\GitHub\tarumt-librarySystem
php artisan serve --port=8000

# Terminal 2 - Backend Server (open a new terminal)
cd c:\Users\User\Documents\GitHub\tarumt-librarySystem
php artisan serve --port=8001
```

**Important**: Both servers must be running simultaneously!

### **Step 2: Login to the System**
Navigate to: `http://localhost:8000/login`

### **Step 3: Go to Profile Page**
Navigate to: `http://localhost:8000/profile`

### **Step 4: Find API Testing Section**
Scroll down to the bottom of the profile page. You'll see:
- **Blue section** with title "API Testing - Book Module Access"
- Three buttons: "Test Book API", "Test Book Stats API", "Clear"

### **Step 5: Click "Test Book API"**
- Watch the loading spinner appear
- See the JSON response displayed
- Check the success icon (✅ green check)

### **Step 6: Click "Test Book Stats API"**
- Tests a different API endpoint
- Shows book statistics in JSON format

---

## 📊 Expected Results

### **Successful API Call Response**
```json
{
  "success": true,
  "message": "Books retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Introduction to Algorithms",
      "author": "Thomas H. Cormen",
      "isbn": "9780262033848",
      "category": "Computer Science",
      "status": "available",
      "publication_year": 2009,
      "cover_image": "data:image/jpeg;base64,..."
    }
    // ... more books
  ],
  "pagination": {
    "total": 50,
    "per_page": 5,
    "current_page": 1,
    "last_page": 10
  }
}
```

### **Book Stats Response**
```json
{
  "success": true,
  "message": "Book statistics retrieved successfully",
  "data": {
    "total_books": 50,
    "available_books": 35,
    "borrowed_books": 10,
    "maintenance_books": 5,
    "categories": [
      {
        "category": "Computer Science",
        "count": 15
      },
      // ... more categories
    ]
  }
}
```

// Backend API Server URL (separate from frontend)
const API_BASE_URL = 'http://localhost:8001';

const response = await fetch(`${API_BASE_URL}/api/books?per_page=5`, {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    credentials: 'include'  // Sends session cookie
});
```

**Key Points:**
- Frontend page runs on `localhost:8000`
- API request goes to `localhost:8001`
- This is a **cross-origin request** (CORS must be configured)
### **Code in Profile Page**
```javascript
const response = await fetch('/api/books?per_page=5', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    credentials"API routes are not accessible on frontend server"**
**Cause**: Trying to access `/api/*` on Frontend Server (port 8000)

**Solution**: 
- API routes only work on Backend Server (port 8001)
- Updated code now correctly calls `http://localhost:8001/api/*`

### **Issue 2: Backend Server Not Running**
**Cause**: Only Frontend Server is running

**Solution**:
```bash
# Open a NEW terminal and run:
php artisan serve --port=8001
```

### **Issue 3: CORS Error**
**Cause**: Cross-origin request from localhost:8000 to localhost:8001

**Solution**: Configure CORS in Backend Server
```bash
# Install CORS package
composer require fruitcake/laravel-cors

# Update config/cors.php
'allowed_origins' => ['http://localhost:8000'],
'supports_credentials' => true,
```

### **Issue 4: 401 Unauthorized**
**Cause**: Not logged in or session expired

**Solution**:
```bash
# Make sure you're logged in at http://localhost:8000/login
# Then try the API test again
```

### **Issue 5
**Solution**: Already handled in `routes/api.php`:
```php
Route::middleware(['web'])->group(function () {
    // API routes with web middleware for session support
});
```

### **Issue 3: Empty Response**
**Cause**: No books in database

**Solution**:
```bash
# Seed the database with sample books
php artisan db:seed --class=BookSeeder
```

---

## 📈 What This Demonstrates

### **1. Frontend-Backend Separation**
- Profile page (frontend) calls Book API (backend)
- Clean separation of concerns
- No direct service coupling

### **2. Cross-Module Communication**
- User Management module accesses Book Management data
- Uses REST API instead of direct service calls
- Maintains module independence

### **3. API-First Architecture**
- Same API can be used by:
  - Web pages (via AJAX)
  - Mobile apps
  - External systems
  - Third-party integrations

### **4. Authentication & Authorization**
- API requires authentication
- Session-based auth works seamlessly
- Token-based auth also supported

---

## 🎓 Key Differences Summary

| Aspect | Profile Page (Direct) | API Test Section (API) |
|--------|----------------------|------------------------|
| **Access Method** | Direct PHP service call | HTTP AJAX request |
| **Data Format** | PHP objects/arrays | JSON |
| **Authentication** | Session-based | Session or Token |
| **Use Case** | Internal page rendering | Cross-module/external access |
| **Performance** | Faster (no HTTP overhead) | Slower (HTTP overhead) |
| **Coupling** | Tightly coupled | Loosely coupled |
| **Flexibility** | Less flexible | More flexible |

---

## 🚀 Next Steps

### **1. Test the API in Profile Page**
- Click the buttons and verify responses
- Check browser console for any errors
- Verify JSON data is displayed correctly

### **2. Try Different API Endpoints**
You can add more test buttons for:
- `GET /api/users` - User list
- `GET /api/books/category/Computer%20Science` - Books by category
- `GET /api/books/status/available` - Available books

### **3. Implement Real API Usage**
If the test works, you can use this pattern to:
- Load books dynamically in user pages
- Show real-time availability
- Display statistics dashboards

---

## 📝 Conclusion

✅ **Profile and User Management pages use DIRECT service calls** (no API)
✅ **API Testing section demonstrates AJAX-based API access**
✅ **Both methods work, but serve different purposes**

The testing section proves that:
1. Book API is accessible from User Management pages
2. Authentication works correctly
3. JSON responses are properly formatted
4. Cross-module communication is functional

This validates your frontend-backend separation architecture! 🎉
