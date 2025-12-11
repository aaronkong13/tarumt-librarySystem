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
