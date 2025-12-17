# TAR UMT Library System — Workflow Guide

This document summarizes the key workflows and how the application implements MVC, ORM, and REST API. Use it for onboarding and quick navigation.

## Overview
- Architecture: Laravel MVC with Service layer, Eloquent ORM, REST API for external consumption.
- Data Access: Controllers call Services which use Eloquent Models. Views never query the database directly.

## MVC Structure
- Controllers: `app/Http/Controllers/*`
	- `BookController`: Staff list, student catalog, CRUD, attaches borrowing stats.
	- `BorrowingController`: Borrow/return/renew/reservations/fines, user history.
	- `Api/BookApiController`: REST endpoints (index/show/store/update/destroy/stats/borrowing-history).
- Services: `app/Services/*`
	- `BookService`: Filtering, sorting, CRUD via Eloquent.
	- `BorrowingService`: Borrow/return/reserve/cancel/renew, fines, history/stats.
- Models (ORM): `app/Models/*` — `Book`, `Borrowing`, `User`, `Reservation`, `Fine`.
- Views: `resources/views/*` — Blade templates (staff table, student cards, pages).

## REST API
- File: `routes/api.php` (registered in `bootstrap/app.php`).
- Endpoints (examples):
	- `GET /api/books` → list books
	- `GET /api/books/{id}` → show book
	- `POST /api/books` → create
	- `PUT /api/books/{id}` → update
	- `DELETE /api/books/{id}` → delete
	- `GET /api/books/category/{category}` → filter by category
	- `GET /api/books/status/{status}` → filter by status
	- `GET /api/books/stats/overview` → basic stats
	- `GET /api/books/{id}/borrowing-history` → borrowing history + stats

## Key Workflows

### 1) Staff Book Management (List + CRUD)
- Sidebar click: "Books Management" → `GET /books`
- Route: `routes/web.php` guarded by `check.staff` middleware
- Controller: `BookController@index`
	- Calls `BookService::getFilteredBooks($request, 10)`
	- Attaches per-book stats via `BorrowingService::getBookBorrowingStats($bookId)`
- View: `resources/views/books/index.blade.php` with table partial `resources/views/layouts/book-table.blade.php`
- Actions:
	- Edit: `GET /books/{book}/edit` → `BookController@edit`
	- Update: `PUT /books/{book}` → `BookController@update`
	- Delete: `DELETE /books/{book}` → `BookController@destroy`

### 2) Borrowing History Modal (Staff)
- Click: Circulation "Total" button in the table
- Frontend: JS in `layouts/book-table.blade.php` calls `GET /books/{id}/borrowing-history`
- Route: `routes/web.php` maps to a controller JSON endpoint
- Controller: `BookController@borrowingHistory`
	- Uses `BorrowingService::getBookBorrowingStats($id)`
	- Returns JSON with totals + per-record details (user, dates, status)
- Modal: Renders stats and list client-side

### 3) Student Book Catalog (Cards)
- Sidebar click: "Books" → `GET /books/catalog`
- Controller: `BookController@catalog`
	- Calls `BookService::getFilteredBooks($request, 12)`
- View: `resources/views/books/student-book.blade.php` with `resources/views/layouts/book-cards.blade.php`
- No staff actions shown (edit/delete hidden and enforced server-side)

## Data Access Policy
- Views must not query the database.
- Controllers should call Services; Services use Eloquent Models.
- **Module Boundaries**: 
  - Book module: Internal CRUD via `BookService` (for book management features only)
  - Other modules (Borrowing, Reservations, etc.): Must access book data via REST API
  - Use `BookApiClient` service for inter-module communication
- External consumers use the REST API under `/api/books/*`

## Inter-Module Communication
When a module needs data from another module (e.g., Borrowing module needs book data):

1. **Don't**: Import and call `BookService` directly from `BorrowingService`
2. **Do**: Use `BookApiClient` to make HTTP requests to the Book API

Example:
```php
// ❌ Wrong - direct service access
$book = Book::findOrFail($bookId);
$book->update(['status' => 'Borrowed']);

// ✅ Correct - via API client
$bookClient = new BookApiClient();
$book = $bookClient->getBook($bookId);
$bookClient->updateBookStatus($bookId, 'Borrowed');
```

This enforces:
- Clear module boundaries
- Independent deployment potential
- API-first architecture
- Easier testing and mocking

## Quick Links
- Web routes: `routes/web.php`
- API routes: `routes/api.php`
- Bootstrap routing registration: `bootstrap/app.php`
- Controllers: `app/Http/Controllers/*`
- Services: `app/Services/*`
- Models: `app/Models/*`
- Views: `resources/views/*`

## Troubleshooting
- If an API endpoint returns 401/403, verify middleware configuration and authentication.
- If routes appear missing, run:

```powershell
php artisan route:clear
php artisan cache:clear
php artisan route:list | Select-String "books"
```

## Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18.x and npm
- MySQL >= 8.0 or MariaDB >= 10.3
- Git

## Prerequisites Installation

Before setting up the project, ensure you have the following software installed:

### 1. Install XAMPP

XAMPP provides Apache, MySQL, and PHP in one package.

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Choose the version with PHP 8.2 or higher
3. Run the installer and follow the installation wizard
4. Install XAMPP to `C:\xampp` (default location)
5. After installation, open XAMPP Control Panel
6. Start the **MySQL** module (Apache is optional since we'll use Laravel's built-in server)

**Important:** Make sure MySQL is running before proceeding with the project setup.

### 2. Install Composer

Composer is a dependency manager for PHP.

1. Download Composer from [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Run the installer (`Composer-Setup.exe` for Windows)
3. Follow the installation wizard
4. When prompted, select the PHP executable from XAMPP (typically `C:\xampp\php\php.exe`)
5. Complete the installation

Verify installation by opening a new terminal and running:
```bash
composer --version
```

### 3. Install Node.js and npm

Node.js includes npm (Node Package Manager) for managing JavaScript dependencies.

1. Download Node.js from [https://nodejs.org/](https://nodejs.org/)
2. Download the **LTS version** (Long Term Support) - version 18.x or higher
3. Run the installer (`node-v*.msi` for Windows)
4. Follow the installation wizard and accept the default settings
5. The installer will automatically add Node.js and npm to your system PATH

Verify installation by opening a new terminal and running:
```bash
node --version
npm --version
```

### 4. Install Git (Optional but Recommended)

Git is required to clone the repository.

1. Download Git from [https://git-scm.com/downloads](https://git-scm.com/downloads)
2. Run the installer
3. Use the recommended default settings during installation
4. Select "Git from the command line and also from 3rd-party software"

Verify installation:
```bash
git --version
```

## Installation

Follow these steps to set up the project on your local machine:

### 1. Clone the Repository

```bash
git clone https://github.com/aaronkong13/tarumt-librarySystem.git
cd tarumt-librarySystem
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit the `.env` file and update the following settings:

```env
APP_NAME="Laravel"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lib_db
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

Create a new MySQL database matching the name in your `.env` file:

```sql
CREATE DATABASE lib_db;
```

### 7. Run Database Migrations

```bash
php artisan migrate
```

### 8. Seed Database (Optional)

If you want to populate the database with sample data:

```bash
php artisan db:seed
```

### 9. Create Storage Link

```bash
php artisan storage:link
```

### 10. Build Frontend Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

## Running the Application

### Development Server

Start the Laravel development server:

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

### Watch for Asset Changes

In a separate terminal, run:

```bash
npm run dev
```

This will watch for changes in your JavaScript and CSS files and automatically recompile them.

## Testing

Run the test suite:

```bash
php artisan test
```

Or using PHPUnit directly:

```bash
./vendor/bin/phpunit
```

## Troubleshooting

### Permission Issues

If you encounter permission issues with storage or cache directories:

```bash
chmod -R 775 storage bootstrap/cache
```

### Clear Application Cache

If you experience unexpected behavior, try clearing the cache:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Composer Dependencies

If composer install fails, try updating:

```bash
composer update
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
