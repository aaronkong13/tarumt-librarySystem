# REST API Documentation - Library Management System

This document describes the REST API endpoints for the Library Management System. The API allows external applications, mobile apps, and frontend frameworks to access book and user data.

## Base URL
```
http://localhost:8000/api
```

## Authentication
Currently the API runs without authentication. For production, add middleware:
```php
Route::middleware('auth:sanctum')->group(function () { ... });
```

---

## BOOK API ENDPOINTS

### 1. Get All Books (List with Filtering & Pagination)
```http
GET /api/books
```

**Query Parameters:**
- `q` (string) - Search keyword (title, author, ISBN)
- `status` (string) - Filter by status: `Available`, `Borrowed`, `Lost`, `Damaged`
- `category` (string) - Filter by category
- `year_from` (integer) - Filter books published from this year
- `year_to` (integer) - Filter books published until this year
- `sort` (string) - Sort option: `title`, `year`
- `page` (integer) - Page number for pagination

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/books?status=Available&category=Science&page=1"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Books retrieved successfully",
  "data": [
    {
      "bookId": 1,
      "title": "The Clean Coder",
      "author": "Robert C. Martin",
      "isbn": "978-0137081073",
      "year": 2011,
      "category": "Computer Science",
      "status": "Available",
      "created_at": "2025-12-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 50,
    "per_page": 15,
    "current_page": 1,
    "last_page": 4
  }
}
```

---

### 2. Get Single Book by ID
```http
GET /api/books/{id}
```

**Path Parameters:**
- `id` (integer) - Book ID (bookId)

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/books/1"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Book retrieved successfully",
  "data": {
    "bookId": 1,
    "title": "The Clean Coder",
    "author": "Robert C. Martin",
    "isbn": "978-0137081073",
    "year": 2011,
    "category": "Computer Science",
    "status": "Available",
    "created_at": "2025-12-15T10:30:00Z"
  }
}
```

---

### 3. Create a New Book
```http
POST /api/books
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Laravel Mastery",
  "author": "Taylor Otwell",
  "isbn": "978-1234567890",
  "year": 2024,
  "category": "Information Technology",
  "status": "Available"
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/books" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Laravel Mastery",
    "author": "Taylor Otwell",
    "isbn": "978-1234567890",
    "year": 2024,
    "category": "Information Technology",
    "status": "Available"
  }'
```

**Example Response (201 Created):**
```json
{
  "success": true,
  "message": "Book created successfully",
  "data": {
    "bookId": 51,
    "title": "Laravel Mastery",
    "author": "Taylor Otwell",
    "isbn": "978-1234567890",
    "year": 2024,
    "category": "Information Technology",
    "status": "Available",
    "created_at": "2025-12-15T10:35:00Z"
  }
}
```

---

### 4. Update a Book
```http
PUT /api/books/{id}
Content-Type: application/json
```

**Path Parameters:**
- `id` (integer) - Book ID

**Request Body:** (Any field can be updated)
```json
{
  "status": "Borrowed",
  "year": 2025
}
```

**Example Request:**
```bash
curl -X PUT "http://localhost:8000/api/books/1" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "Borrowed"
  }'
```

**Example Response:**
```json
{
  "success": true,
  "message": "Book updated successfully",
  "data": {
    "bookId": 1,
    "title": "The Clean Coder",
    "author": "Robert C. Martin",
    "isbn": "978-0137081073",
    "year": 2011,
    "category": "Computer Science",
    "status": "Borrowed",
    "updated_at": "2025-12-15T10:40:00Z"
  }
}
```

---

### 5. Delete a Book (Soft Delete)
```http
DELETE /api/books/{id}
```

**Path Parameters:**
- `id` (integer) - Book ID

**Example Request:**
```bash
curl -X DELETE "http://localhost:8000/api/books/1"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Book deleted successfully"
}
```

**Error Response (Cannot delete borrowed books):**
```json
{
  "success": false,
  "message": "Cannot delete a borrowed book"
}
```

---

### 6. Get Books by Status
```http
GET /api/books/status/{status}
```

**Path Parameters:**
- `status` (string) - Status value: `Available`, `Borrowed`, `Lost`, `Damaged`

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/books/status/Available"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Books with status 'Available' retrieved successfully",
  "data": [
    { "bookId": 1, "title": "The Clean Coder", ... },
    { "bookId": 2, "title": "Clean Code", ... }
  ]
}
```

---

### 7. Get Books by Category
```http
GET /api/books/category/{category}
```

**Path Parameters:**
- `category` (string) - Category name

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/books/category/Computer%20Science"
```

---

### 8. Get Book Statistics
```http
GET /api/books/stats/overview
```

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/books/stats/overview"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Book statistics retrieved successfully",
  "data": {
    "total_books": 50,
    "available": 35,
    "borrowed": 12
  }
}
```

---

## USER API ENDPOINTS

### 1. Get All Users (Paginated)
```http
GET /api/users
```

**Query Parameters:**
- `per_page` (integer) - Items per page (default: 10)
- `page` (integer) - Page number

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/users?per_page=20&page=1"
```

**Example Response:**
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Admin",
      "email": "admin@library.edu",
      "role": "Admin",
      "created_at": "2025-12-15T10:30:00Z"
    }
  ],
  "pagination": {
    "total": 100,
    "per_page": 20,
    "current_page": 1,
    "last_page": 5
  }
}
```

---

### 2. Get Single User by ID
```http
GET /api/users/{id}
```

**Path Parameters:**
- `id` (integer) - User ID

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/users/1"
```

---

### 3. Create a New User
```http
POST /api/users
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jane Student",
  "email": "jane@library.edu",
  "password": "secure_password_123",
  "role": "Student"
}
```

**Example Request:**
```bash
curl -X POST "http://localhost:8000/api/users" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Student",
    "email": "jane@library.edu",
    "password": "secure_password_123",
    "role": "Student"
  }'
```

**Example Response (201 Created):**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "id": 101,
    "name": "Jane Student",
    "email": "jane@library.edu",
    "role": "Student",
    "created_at": "2025-12-15T10:45:00Z"
  }
}
```

---

### 4. Update a User
```http
PUT /api/users/{id}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Jane Doe",
  "role": "Staff"
}
```

**Example Request:**
```bash
curl -X PUT "http://localhost:8000/api/users/1" \
  -H "Content-Type: application/json" \
  -d '{
    "role": "Staff"
  }'
```

---

### 5. Delete a User (Soft Delete)
```http
DELETE /api/users/{id}
```

**Example Request:**
```bash
curl -X DELETE "http://localhost:8000/api/users/1"
```

---

### 6. Get Users by Role
```http
GET /api/users/role/{role}
```

**Path Parameters:**
- `role` (string) - Role: `Student`, `Staff`, `Admin`

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/users/role/Staff"
```

---

### 7. Search Users
```http
GET /api/users/search/query?q={search_term}
```

**Query Parameters:**
- `q` (string) - Search by name or email

**Example Request:**
```bash
curl -X GET "http://localhost:8000/api/users/search/query?q=john"
```

---

### 8. Get User Statistics
```http
GET /api/users/stats/overview
```

**Example Response:**
```json
{
  "success": true,
  "message": "User statistics retrieved successfully",
  "data": {
    "total_users": 100,
    "students": 75,
    "staff": 20,
    "admins": 5
  }
}
```

---

## Error Responses

### 404 - Not Found
```json
{
  "success": false,
  "message": "Book not found"
}
```

### 422 - Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required"],
    "isbn": ["The isbn has already been taken"]
  }
}
```

### 500 - Server Error
```json
{
  "success": false,
  "message": "Error creating book",
  "error": "Exception message"
}
```

---

## Usage Examples

### JavaScript/Fetch API
```javascript
// Get all books
fetch('http://localhost:8000/api/books')
  .then(res => res.json())
  .then(data => console.log(data.data));

// Create a new book
fetch('http://localhost:8000/api/books', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    title: 'New Book',
    author: 'Author Name',
    isbn: '978-1234567890',
    year: 2025,
    category: 'Science',
    status: 'Available'
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

### Python/Requests
```python
import requests

# Get all books
response = requests.get('http://localhost:8000/api/books')
books = response.json()

# Create a new book
response = requests.post('http://localhost:8000/api/books', json={
    'title': 'New Book',
    'author': 'Author Name',
    'isbn': '978-1234567890',
    'year': 2025,
    'category': 'Science',
    'status': 'Available'
})
print(response.json())
```

---

## Summary

This REST API provides complete access to book and user data from the Library Management System. All endpoints return JSON responses with consistent structure containing `success`, `message`, and `data` fields.

**Key Features:**
- ✅ Full CRUD operations for Books and Users
- ✅ Filtering, searching, and pagination support
- ✅ Statistics endpoints for analytics
- ✅ Error handling with meaningful messages
- ✅ RESTful design following HTTP standards
- ✅ Can be consumed by any external application
