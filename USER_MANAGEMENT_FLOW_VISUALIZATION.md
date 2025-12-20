# User Management - Flow Visualization

## 📊 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                    USER MANAGEMENT MODULE                           │
│                  Frontend-Backend Separation                        │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐              ┌──────────────────────┐
│   FRONTEND (Web UI)  │              │  BACKEND (REST API)  │
│   Port 8000          │              │   Port 8001          │
│                      │              │                      │
│  UserController      │              │  UserApiController   │
│  ├─ index()          │              │  ├─ index()          │
│  ├─ create()         │              │  ├─ show()           │
│  ├─ store()          │              │  ├─ store()          │
│  ├─ show()           │              │  ├─ update()         │
│  ├─ edit()           │              │  ├─ destroy()        │
│  ├─ update()         │              │  ├─ getByRole()      │
│  ├─ destroy()        │              │  ├─ search()         │
│  ├─ showProfile()    │              │  └─ stats()          │
│  ├─ editProfile()    │              │                      │
│  └─ updateProfile()  │              │  Returns: JSON       │
│                      │              │                      │
│  Returns: HTML       │              │                      │
└──────────┬───────────┘              └──────────┬───────────┘
           │                                     │
           │                                     │
           └─────────────┬───────────────────────┘
                         │
                    ┌────▼─────┐
                    │          │
                    │  Shared  │
                    │  Layers  │
                    │          │
                    └────┬─────┘
                         │
           ┌─────────────┼─────────────┐
           │             │             │
      ┌────▼────┐   ┌────▼────┐  ┌────▼─────┐
      │ User    │   │ User    │  │  MySQL   │
      │ Service │   │ Model   │  │ Database │
      └─────────┘   └─────────┘  └──────────┘
```

---

## 🔄 Flow 1: Web UI Request (Frontend)

### **Scenario: Staff views user list**

```
┌─────────────────────────────────────────────────────────────────┐
│  Step 1: Browser Request                                        │
└─────────────────────────────────────────────────────────────────┘

Browser
  │
  │ GET http://localhost:8000/users
  │
  ▼
┌────────────────────────────────────┐
│  routes/web.php                    │
│  Route::get('/users', ...)         │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  UserController@index()            │
│  (Port 8000 - Frontend Server)    │
│                                    │
│  • Check authorization             │
│  • Call UserService                │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  UserService::getFilteredUsers()   │
│                                    │
│  • Apply filters                   │
│  • Paginate results                │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  User Model (Eloquent)             │
│  • Query database                  │
│  • Eager load relationships        │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  MySQL Database                    │
│  SELECT * FROM users ...           │
└────────────┬───────────────────────┘
             │
             ▼ (Raw Data)
┌────────────────────────────────────┐
│  UserController (Format)           │
│  • Remove sensitive data           │
│  • Prepare for view                │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  users/index.blade.php             │
│  Render HTML with user list        │
└────────────┬───────────────────────┘
             │
             ▼ (HTML Response)
┌────────────────────────────────────┐
│  Browser Display                   │
│  User sees formatted table         │
└────────────────────────────────────┘
```

---

## 🔄 Flow 2: API Request (Backend)

### **Scenario: External module requests user data via API**

```
┌─────────────────────────────────────────────────────────────────┐
│  Step 2: API Request                                            │
└─────────────────────────────────────────────────────────────────┘

External Module / AJAX / Mobile App
  │
  │ GET http://localhost:8001/api/users
  │ Headers: Accept: application/json
  │          Authorization: Bearer token
  │
  ▼
┌────────────────────────────────────┐
│  routes/api.php                    │
│  Route::get('/api/users', ...)     │
│  Middleware: api_token_auth        │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  Middleware: api_token_auth        │
│  • Verify authentication           │
│  • Check API token/session         │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  Api\UserApiController@index()     │
│  (Port 8001 - Backend Server)      │
│                                    │
│  • Validate request                │
│  • Call UserService                │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  UserService::getPaginatedUsers()  │
│                                    │
│  • Apply pagination                │
│  • Get user data                   │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  User Model (Eloquent)             │
│  • Query database                  │
│  • Get paginated results           │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  MySQL Database                    │
│  SELECT * FROM users ...           │
└────────────┬───────────────────────┘
             │
             ▼ (Raw Data with BLOB)
┌────────────────────────────────────┐
│  UserApiController::cleanUserData()│
│  • Convert profile_image to base64│
│  • Remove binary data              │
│  • Prevent UTF-8 errors            │
└────────────┬───────────────────────┘
             │
             ▼ (Cleaned Data)
┌────────────────────────────────────┐
│  JSON Response                     │
│  {                                 │
│    "success": true,                │
│    "message": "Users retrieved",   │
│    "data": [...],                  │
│    "pagination": {...}             │
│  }                                 │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  Consumer Receives JSON            │
│  Parse and use data                │
└────────────────────────────────────┘
```

---

## 🔄 Flow 3: Cross-Module API Access

### **Scenario: Borrowing module needs user information**

```
┌─────────────────────────────────────────────────────────────────┐
│  Step 3: Cross-Module Communication via API                    │
└─────────────────────────────────────────────────────────────────┘

BorrowingController
  │
  │ Need to verify user status before lending book
  │
  ▼
┌────────────────────────────────────┐
│  UserApiClient (Consumer)          │
│  $client = new UserApiClient()     │
│  $user = $client->getUser($id)     │
└────────────┬───────────────────────┘
             │
             │ HTTP Request
             │ GET http://localhost:8001/api/users/{id}
             │
             ▼
┌────────────────────────────────────┐
│  Backend API Server (Port 8001)    │
│  routes/api.php                    │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  UserApiController@show($id)       │
│  • Validate user ID                │
│  • Call UserService                │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  UserService::getUserById($id)     │
│  • Find user in database           │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  User Model                        │
│  User::find($id)                   │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  MySQL Database                    │
│  SELECT * FROM users WHERE id = ?  │
└────────────┬───────────────────────┘
             │
             ▼ (User Data)
┌────────────────────────────────────┐
│  UserApiController                 │
│  • Clean BLOB data                 │
│  • Convert to base64               │
│  • Return JSON                     │
└────────────┬───────────────────────┘
             │
             │ JSON Response
             │ {
             │   "success": true,
             │   "data": {
             │     "id": 1,
             │     "name": "John",
             │     "role": "Student",
             │     "status": "active"
             │   }
             │ }
             │
             ▼
┌────────────────────────────────────┐
│  UserApiClient                     │
│  Parse JSON response               │
│  return $data['data']              │
└────────────┬───────────────────────┘
             │
             ▼
┌────────────────────────────────────┐
│  BorrowingController               │
│  • Receives user data              │
│  • Validates user status           │
│  • Continues with borrowing logic  │
└────────────────────────────────────┘
```

---

## 🔀 Flow 4: CRUD Operations Comparison

### **Frontend vs Backend**

```
┌─────────────────────────────────────────────────────────────────┐
│  CREATE USER                                                    │
└─────────────────────────────────────────────────────────────────┘

FRONTEND (Web UI)                    BACKEND (API)
─────────────────────               ───────────────

POST /users                          POST /api/users
  ↓                                    ↓
UserController@store()               UserApiController@store()
  ↓                                    ↓
Validate input                       Validate JSON input
  ↓                                    ↓
UserFactory::create()                UserService::createUser()
  ↓                                    ↓
User Model → DB                      User Model → DB
  ↓                                    ↓
Redirect to users list               Return JSON response
  ↓                                    ↓
Return HTML                          { "success": true, "data": {...} }


┌─────────────────────────────────────────────────────────────────┐
│  READ USER                                                      │
└─────────────────────────────────────────────────────────────────┘

FRONTEND (Web UI)                    BACKEND (API)
─────────────────────               ───────────────

GET /users                           GET /api/users
  ↓                                    ↓
UserController@index()               UserApiController@index()
  ↓                                    ↓
UserService::getFilteredUsers()      UserService::getPaginatedUsers()
  ↓                                    ↓
User Model → DB                      User Model → DB
  ↓                                    ↓
Format for Blade                     cleanUserData() - Remove BLOB
  ↓                                    ↓
users/index.blade.php                JSON with pagination
  ↓                                    ↓
Return HTML Table                    { "data": [...], "pagination": {...} }


┌─────────────────────────────────────────────────────────────────┐
│  UPDATE USER                                                    │
└─────────────────────────────────────────────────────────────────┘

FRONTEND (Web UI)                    BACKEND (API)
─────────────────────               ───────────────

PUT /users/{id}                      PUT /api/users/{id}
  ↓                                    ↓
UserController@update()              UserApiController@update()
  ↓                                    ↓
Validate input                       Validate JSON input
  ↓                                    ↓
UserFactory::update()                UserService::updateUser()
  ↓                                    ↓
User Model → DB                      User Model → DB
  ↓                                    ↓
Redirect with success msg            Return JSON response
  ↓                                    ↓
Return HTML                          { "success": true, "data": {...} }


┌─────────────────────────────────────────────────────────────────┐
│  DELETE USER                                                    │
└─────────────────────────────────────────────────────────────────┘

FRONTEND (Web UI)                    BACKEND (API)
─────────────────────               ───────────────

DELETE /users/{id}                   DELETE /api/users/{id}
  ↓                                    ↓
UserController@destroy()             UserApiController@destroy()
  ↓                                    ↓
Check authorization                  Check middleware auth
  ↓                                    ↓
UserService::deleteUser()            UserService::deleteUser()
  ↓                                    ↓
Soft delete (deleted_at)             Soft delete (deleted_at)
  ↓                                    ↓
Redirect with message                Return JSON response
  ↓                                    ↓
Return HTML                          { "success": true, "message": "..." }
```

---

## 📊 Data Flow: BLOB Handling

### **Problem: UTF-8 Encoding Error**

```
┌─────────────────────────────────────────────────────────────────┐
│  BLOB Data Handling in API                                      │
└─────────────────────────────────────────────────────────────────┘

Database (MySQL)
  │
  │ BLOB field: profile_image
  │ Binary data: 0xFF 0xD8 0xFF 0xE0 ...
  │
  ▼
User Model
  │
  │ Eloquent retrieves raw binary
  │
  ▼
UserApiController::index()
  │
  │ Before cleaning: ❌ JSON encoding error
  │ "Malformed UTF-8 characters"
  │
  ▼
cleanUserData() Method
  │
  ├─ For List Endpoints:
  │    └─ Convert to base64: "data:image/jpeg;base64,/9j/4AAQ..."
  │       OR remove: profile_image = null
  │
  └─ For Single User:
       └─ Convert to base64 with data URI
  │
  ▼
JSON Response
  │
  │ {
  │   "profile_image": "data:image/jpeg;base64,/9j/4AAQ..."
  │ }
  │
  ▼
Consumer
  │
  │ Frontend can use directly:
  │ <img src="data:image/jpeg;base64,..." />
  │
  ✅ Success!
```

---

## 🔐 Authentication & Authorization Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  Security Flow                                                  │
└─────────────────────────────────────────────────────────────────┘

FRONTEND REQUEST                     API REQUEST
────────────────────                ───────────

Browser                              External Module / AJAX
  │                                    │
  │ Session Cookie                     │ API Token / Session
  │                                    │
  ▼                                    ▼
Web Middleware                       API Middleware
  │                                    │
  ├─ web                               ├─ api_token_auth
  ├─ auth                              └─ auth:sanctum
  └─ check.staff (for write)           │
  │                                    │
  ▼                                    ▼
UserController                       UserApiController
  │                                    │
  ├─ AccessControlService              ├─ Middleware check
  │  • canViewUser()                   │  • Token validation
  │  • canEditUser()                   │  • Session validation
  │  • canDeactivateUser()             │
  │                                    │
  ▼                                    ▼
Authorized Action                    Authorized API Action
  │                                    │
  • View users list (Staff+)           • GET endpoints (All auth)
  • Create users (Staff only)          • POST/PUT/DELETE (Staff+)
  • Edit users (Staff+)                │
  • Deactivate users (Staff+)          │
  │                                    │
  ▼                                    ▼
HTML Response                        JSON Response
```

---

## 🌐 Frontend-Backend Communication

```
┌─────────────────────────────────────────────────────────────────┐
│  Dual-Server Architecture                                       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────┐     ┌──────────────────────────┐
│  Frontend Server (8000)         │     │  Backend Server (8001)   │
│  ────────────────────────        │     │  ──────────────────────  │
│                                 │     │                          │
│  • Serves HTML pages            │     │  • Serves JSON APIs      │
│  • Blade templates              │     │  • No HTML rendering     │
│  • Session authentication       │     │  • Token/API auth        │
│  • CSRF protection              │     │  • CORS enabled          │
│                                 │     │                          │
│  UserController                 │     │  UserApiController       │
│  └─ Returns HTML                │     │  └─ Returns JSON         │
│                                 │     │                          │
└────────────┬────────────────────┘     └───────────┬──────────────┘
             │                                      │
             │  If frontend needs API data:        │
             │  ────────────────────────           │
             │                                      │
             │  JavaScript AJAX/Fetch               │
             │  fetch('http://localhost:8001/...')  │
             │  ──────────────────────────────────▶ │
             │                                      │
             │ ◀────────────────────────────────────│
             │  JSON Response                       │
             │  { "success": true, "data": [...] }  │
             │                                      │
             │                                      │
             └──────────────┬───────────────────────┘
                            │
                       ┌────▼────┐
                       │ Shared  │
                       │ Service │
                       │  Layer  │
                       └────┬────┘
                            │
                       ┌────▼────┐
                       │Database │
                       └─────────┘
```

---

## 📈 Complete Request Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│  End-to-End User Management Request Lifecycle                  │
└─────────────────────────────────────────────────────────────────┘

1. User Action
   │
   │ Staff clicks "View Users" in browser
   │
   ▼

2. HTTP Request
   │
   │ GET http://localhost:8000/users
   │
   ▼

3. Router
   │
   │ routes/web.php matches route
   │ Applies middleware: web, auth, check.staff
   │
   ▼

4. Middleware Stack
   │
   ├─ StartSession
   ├─ VerifyCsrfToken
   ├─ Authenticate
   └─ CheckStaff (custom)
   │
   ▼

5. Controller
   │
   │ UserController@index()
   │ • Verify authorization via AccessControlService
   │ • Call UserService
   │
   ▼

6. Service Layer
   │
   │ UserService::getFilteredUsers()
   │ • Apply request filters
   │ • Build query
   │ • Paginate results
   │
   ▼

7. Model Layer
   │
   │ User::query()->where(...)->paginate()
   │
   ▼

8. Database Query
   │
   │ MySQL executes:
   │ SELECT * FROM users
   │ WHERE deleted_at IS NULL
   │ ORDER BY created_at DESC
   │ LIMIT 5 OFFSET 0
   │
   ▼

9. Result Processing
   │
   │ User models with relationships loaded
   │ Pagination metadata attached
   │
   ▼

10. View Rendering
    │
    │ users/index.blade.php
    │ • Loop through users
    │ • Display in table format
    │ • Add action buttons
    │ • Compile to HTML
    │
    ▼

11. HTTP Response
    │
    │ Status: 200 OK
    │ Content-Type: text/html
    │ Body: Complete HTML page
    │
    ▼

12. Browser Display
    │
    │ User sees formatted user list
    │ Can interact with buttons
    │
    ✓ Complete!


─────────────────────────────────────────────────────────────────

PARALLEL: If this was an API request to localhost:8001/api/users:

Steps 1-4: Similar (different port, api middleware)
Steps 5-8: Identical (same service and model layer)
Step 9: Add data cleaning (BLOB to base64)
Step 10: Format as JSON instead of HTML
Step 11: Return JSON response
Step 12: Consumer parses and uses JSON data
```

---

## 🎯 Key Differences Summary

| Aspect | Frontend (Web UI) | Backend (API) |
|--------|------------------|---------------|
| **Port** | 8000 | 8001 |
| **Controller** | `UserController` | `UserApiController` |
| **Routes** | `routes/web.php` | `routes/api.php` |
| **Returns** | HTML (Blade views) | JSON |
| **Authentication** | Session cookies | Token/Session |
| **Middleware** | `web`, `auth`, `check.staff` | `api_token_auth`, `check.staff` |
| **CSRF** | Required | Optional |
| **Authorization** | `AccessControlService` | Middleware-based |
| **Error Response** | Redirect with flash | JSON error object |
| **Success Response** | Redirect with flash | JSON success object |
| **Image Handling** | Direct BLOB display | Base64 encoded |
| **Pagination** | Laravel paginator | JSON pagination meta |
| **Consumer** | Browser | External apps/AJAX |

---

## ✅ Architecture Benefits

### **1. Separation of Concerns**
- Frontend focuses on UI/UX
- Backend focuses on data logic
- Clear boundaries prevent coupling

### **2. Scalability**
- Can scale frontend and backend independently
- Can add more backend servers for API load

### **3. Flexibility**
- Same API serves multiple consumers:
  - Web frontend (AJAX)
  - Mobile apps
  - External integrations
  - Third-party services

### **4. Maintainability**
- Changes to UI don't affect API
- Changes to API structure documented clearly
- Easier to test separately

### **5. Security**
- Different authentication strategies
- API rate limiting possible
- Token-based access control

---

## 🔄 Migration Path

**From monolithic to separated:**

```
BEFORE (Monolithic)                  AFTER (Separated)
───────────────────                 ─────────────────

UserController                       UserController (Web)
├─ index() → HTML                    ├─ index() → HTML
├─ store() → Redirect                ├─ store() → Redirect
└─ update() → Redirect               └─ update() → Redirect
  │                                    │
  └─ Calls UserService                 └─ Calls UserService
       │                                     │
       └─ User Model                         └─ User Model
            │                                     │
            └─ Database                           └─ Database
                                                       ▲
                                                       │
                                      UserApiController (API)
                                      ├─ index() → JSON
                                      ├─ store() → JSON
                                      └─ update() → JSON
                                        │
                                        └─ Calls UserService
                                             │
                                             └─ User Model
                                                  │
                                                  └─ Database
```

---

This visualization shows the complete separation architecture for User Management! 🚀
