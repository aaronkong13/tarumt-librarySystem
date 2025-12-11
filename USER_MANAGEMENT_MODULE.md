# User Management Module Documentation

## Overview
Complete User Management module for TARUMT Library System with Factory Pattern implementation and three validation types.

## 🎯 Design Pattern: Factory Pattern

### Implementation
**Location:** `app/Factories/UserFactory.php`

The Factory Pattern is used to create different types of users (Student, Staff, and Admin) through a centralized factory class:

```php
// Create Student
UserFactory::createStudent($data);

// Create Staff  
UserFactory::createStaff($data);

// Create Admin
UserFactory::createAdmin($data);

// Update user
UserFactory::update($user, $data);

// Deactivate user
UserFactory::deactivate($user);
```

### Benefits
- Centralized user creation logic
- Easy to extend with new user types
- Consistent user initialization
- Simplified password hashing and data handling

---

## 👥 Role-Based Access Control System

### Three User Roles

#### 1. **Student**
- Can **only** CRUD their own profile
- Cannot access other users' information
- Cannot create new users
- **Self-registration allowed** (public registration)

#### 2. **Staff**
- Can CRUD **Students** and their own profile
- **Cannot** CRUD other Staff members
- Can create **Student accounts only**
- Cannot self-register (must be created by Admin)

#### 3. **Admin**
- Can manage **all users** (Students and Staff)
- Can create **Student and Staff accounts**
- **Cannot** create other Admin accounts (only one Admin exists)
- System has one default Admin account

### Permission Matrix

| Action | Student | Staff | Admin |
|--------|---------|-------|-------|
| Self-register | ✅ | ❌ | ❌ |
| View own profile | ✅ | ✅ | ✅ |
| Edit own profile | ✅ | ✅ | ✅ |
| View Students | ❌ | ✅ | ✅ |
| Edit Students | ❌ | ✅ | ✅ |
| Create Students | ❌ | ✅ | ✅ |
| Deactivate Students | ❌ | ✅ | ✅ |
| View Staff | ❌ | ❌ | ✅ |
| Edit Staff | ❌ | ❌ | ✅ |
| Create Staff | ❌ | ❌ | ✅ |
| Deactivate Staff | ❌ | ❌ | ✅ |
| Create Admin | ❌ | ❌ | ❌ |

---

## ✅ Three Validation Types

### 1. **Input Validation** 
**Location:** `app/Services/InputValidationService.php`

Validates all user inputs to ensure data integrity:

- **Registration Validation**
  - Name: Required, 2-255 characters
  - Email: Required, valid email format, unique
  - Password: Required, minimum 8 characters, confirmed
  - Role: Required, must be 'Student' or 'Staff'
  - Phone: Optional, valid phone format
  - Address: Optional, max 500 characters

- **Profile Update Validation**
  - Same as registration but allows existing user's email
  - Password optional for updates

- **Password Reset Validation**
  - Email must exist in database
  - Token verification
  - New password minimum 8 characters with confirmation

**Usage Example:**
```php
$validatedData = InputValidationService::validateRegistration($request->all());
```

### 2. **Authentication and Password Management**
**Location:** `app/Services/AuthenticationService.php`

Handles secure authentication and password operations:

- **User Authentication**
  - Verifies credentials
  - Checks if user account is active
  - Prevents login for deactivated users
  - Session management

- **Password Operations**
  - Secure password hashing
  - Password verification
  - Password reset token generation
  - Password change functionality

- **Session Management**
  - Login/Logout
  - Session regeneration
  - Remember token handling

**Usage Example:**
```php
// Authenticate user
if (AuthenticationService::authenticate($credentials)) {
    // User logged in successfully
}

// Change password
AuthenticationService::changePassword($user, $newPassword);

// Send reset link
AuthenticationService::sendPasswordResetLink($email);
```

### 3. **Access Control**
**Location:** `app/Services/AccessControlService.php`

Implements role-based access control (RBAC):

- **Role Checking**
  - Staff vs Student permissions
  - Action-based authorization (view, create, edit, delete)
  - Resource-based access control

- **Permission Matrix**
  - **Staff:** Full user management access
  - **Student:** Own profile access only

- **Access Rules**
  - Users can view/edit their own profile
  - Staff can manage all users
  - Students cannot access user management
  - Users cannot deactivate themselves

**Usage Example:**
```php
// Check permission
AccessControlService::authorize('create', 'user');

// Check if can edit user
if (AccessControlService::canEditUser($user)) {
    // Allow edit
}

// Check if staff
if (AccessControlService::isStaff()) {
    // Show staff features
}
```

**Middleware:** `CheckStaff` middleware protects staff-only routes

---

## 📋 Module Features

### 1. User Registration
- **Students can self-register** (public registration)
- **Staff cannot self-register** (must be created by Admin)
- Staff can create Student accounts
- Admin can create Student and Staff accounts
- Validates unique email to prevent duplicates
- Factory Pattern creates users based on role
- Input Validation ensures data quality

**Routes:**
- `GET/POST /register` - Student self-registration only
- Staff/Admin create users via User Management section

**Controller:** `AuthController@register` (Student only), `UserController@store` (Staff/Admin)
**View:** `resources/views/auth/register.blade.php`

### 2. Login / Logout
- All registered users can login
- Role-based authentication (Student/Staff)
- Authentication Service verifies credentials
- Active account status check
- Secure logout with session invalidation

**Routes:**
- `GET/POST /login` - Login
- `POST /logout` - Logout

**Controller:** `AuthController@login`, `AuthController@logout`
**View:** `resources/views/auth/login.blade.php`

### 3. Password Reset
- Request password reset via email
- Secure token-based reset system
- Password validation on reset
- Authentication Service handles process

**Routes:**
- `GET/POST /forgot-password` - Request reset
- `GET/POST /reset-password/{token}` - Reset password

**Controller:** `AuthController` (password reset methods)
**Views:** 
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`

### 4. Profile Management
- View own profile
- Update own information
- Optional password change
- Access Control ensures proper permissions

**Routes:**
- `GET /users/{user}` - View profile
- `GET /users/{user}/edit` - Edit form
- `PUT /users/{user}` - Update profile

**Controller:** `UserController@show`, `UserController@edit`, `UserController@update`
**Views:**
- `resources/views/users/show.blade.php`
- `resources/views/users/edit.blade.php`

### 5. Add / Edit / Deactivate Member
- **Staff can create/edit/deactivate Students only**
- **Admin can create/edit/deactivate all users (Students and Staff)**
- Cannot create Admin accounts (system has only one Admin)
- Staff cannot manage other Staff members
- Deactivated records retained for history (soft delete)
- Can reactivate deactivated users
- Access Control restricts operations based on role

**Routes:**
- `GET /users` - List all users (Staff/Admin)
- `GET /users/create` - Create form (Staff/Admin)
- `POST /users` - Store new user (Staff/Admin)
- `DELETE /users/{user}` - Deactivate (based on permissions)
- `POST /users/{id}/restore` - Reactivate (Admin only)

**Controller:** `UserController` (all methods)
**Views:**
- `resources/views/users/index.blade.php`
- `resources/views/users/create.blade.php`

---

## 🔑 Default Admin Account

The system comes with one default Admin account:

- **Email:** admin@library.com
- **Password:** admin123
- **Role:** Admin

⚠️ **Important:** Change the default password after first login!

To create the Admin account, run:
```bash
php artisan db:seed --class=AdminSeeder
```

---

## 📁 File Structure

```
app/
├── Factories/
│   └── UserFactory.php                 # Factory Pattern implementation
├── Services/
│   ├── InputValidationService.php      # Input validation
│   ├── AuthenticationService.php       # Authentication & password
│   └── AccessControlService.php        # Access control & permissions
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login, register, password reset
│   │   └── UserController.php          # User CRUD operations
│   └── Middleware/
│       └── CheckStaff.php              # Staff-only middleware
└── Models/
    └── User.php                         # Updated with role, status fields

database/
└── migrations/
    ├── 2025_12_11_*_add_user_management_fields_to_users_table.php
    └── 2025_12_11_*_update_users_table_add_admin_role.php
└── seeders/
    └── AdminSeeder.php                 # Creates default Admin user

resources/views/
├── layouts/
│   └── app.blade.php                   # Main layout with navigation
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
├── users/
│   ├── index.blade.php                 # User list (Staff only)
│   ├── create.blade.php                # Create user (Staff only)
│   ├── show.blade.php                  # View profile
│   └── edit.blade.php                  # Edit profile
└── dashboard.blade.php                 # Main dashboard

routes/
└── web.php                             # All user management routes
```

---

## 🗄️ Database Schema

### Users Table Fields
- `id` - Primary key
- `name` - User full name
- `email` - Unique email (for login)
- `password` - Hashed password
- `role` - ENUM('Student', 'Staff', 'Admin')
- `phone` - Optional phone number
- `address` - Optional address
- `status` - ENUM('Active', 'Inactive')
- `email_verified_at` - Email verification timestamp
- `remember_token` - Remember me token
- `created_at` - Registration date
- `updated_at` - Last update
- `deleted_at` - Soft delete timestamp (for deactivated users)

---

## 🔐 Security Features

1. **Password Security**
   - Bcrypt hashing
   - Minimum 8 characters requirement
   - Password confirmation required

2. **Access Control**
   - Role-based permissions
   - Middleware protection for staff routes
   - User can only edit own profile (unless Staff)
   - Cannot deactivate yourself

3. **Input Validation**
   - Email format validation
   - Unique email constraint
   - Phone number format validation
   - XSS protection through Laravel

4. **Session Security**
   - Session regeneration on login
   - Session invalidation on logout
   - CSRF token protection on forms

---

## 🚀 How to Test

1. **Start the server:**
   ```bash
   php artisan serve
   ```

2. **Create Admin account (if not exists):**
   ```bash
   php artisan db:seed --class=AdminSeeder
   ```
   - Email: admin@library.com
   - Password: admin123

3. **Register a new student:**
   - Go to http://localhost:8000/register
   - Fill in the form (role is automatically set to Student)
   - Submit

4. **Login as Admin:**
   - Go to http://localhost:8000/login
   - Use admin credentials
   - Access "User Management" in navigation
   - Try creating Staff and Student accounts
   - Try editing/deactivating users

5. **Create a Staff member (as Admin):**
   - Login as Admin
   - Go to User Management
   - Click "Add New User"
   - Select "Staff" role
   - Fill in details and submit

6. **Login as Staff:**
   - Logout from Admin
   - Login with Staff credentials
   - Can only create Student accounts (no Staff option)
   - Can edit Students but not other Staff
   - Cannot see or edit Admin

7. **Login as Student:**
   - Can only access own profile
   - No access to User Management section
   - Cannot create or manage other users

8. **Test Access Control:**
   - Try accessing other users' profiles with different roles
   - Verify Staff cannot edit other Staff
   - Verify Student cannot access User Management

9. **Test Password Reset:**
   - Click "Forgot Password" on login
   - Enter email (requires mail configuration)

10. **Test Profile Update:**
    - View your profile
    - Click "Edit Profile"
    - Update information
    - Optionally change password

---

## ✨ Key Highlights

### Factory Pattern Application
- ✅ Creates Student users via `UserFactory::createStudent()`
- ✅ Creates Staff users via `UserFactory::createStaff()`
- ✅ Creates Admin users via `UserFactory::createAdmin()`
- ✅ Updates users via `UserFactory::update()`
- ✅ Deactivates users via `UserFactory::deactivate()`

### Three Validation Types
1. ✅ **Input Validation:** All forms validated for data integrity
2. ✅ **Authentication:** Secure login, password verification, token-based resets
3. ✅ **Access Control:** Role-based permissions with granular access rules

### Three-Tier Role System
1. ✅ **Student:** Self-registration, own profile only
2. ✅ **Staff:** Create/manage Students, cannot manage Staff
3. ✅ **Admin:** Full control, can manage Students and Staff

### All Requirements Met
- ✅ User Registration (Student self-registration only)
- ✅ Login/Logout with role authentication
- ✅ Password Reset functionality
- ✅ Profile Management (View & Update with role-based access)
- ✅ Add/Edit/Deactivate Members (role-based permissions)
- ✅ Retain deactivated records (Soft Delete)
- ✅ Staff can only create Students
- ✅ Admin can create Students and Staff
- ✅ Single Admin system (no Admin creation)

---

## 📝 Notes

- The module uses Laravel's built-in authentication features
- Soft deletes preserve user history
- Bootstrap 5 is used for responsive UI
- All routes are protected with appropriate middleware
- Error messages provide clear feedback to users
- Success messages confirm completed actions

---

## 🎓 Academic Context

This module demonstrates:
- **Design Pattern:** Factory Pattern for object creation
- **Security:** Three-layer validation approach
- **Architecture:** Service-oriented design
- **Best Practices:** Separation of concerns, DRY principle
- **Database:** Proper schema design with relationships and soft deletes
- **User Experience:** Complete authentication flow with proper feedback
