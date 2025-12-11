<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Access Control Service
 * Handles role-based access control and permissions
 * 
 * Permission Rules:
 * - Student: Can only CRUD their own profile
 * - Staff: Can CRUD Students and their own profile, cannot CRUD other Staff
 * - Admin: Can manage all users (Students and Staff), cannot create other Admins
 */
class AccessControlService
{
    /**
     * Check if user is Admin
     */
    public static function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'Admin';
    }

    /**
     * Check if user is Staff
     */
    public static function isStaff(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'Staff';
    }

    /**
     * Check if user is Student
     */
    public static function isStudent(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'Student';
    }

    /**
     * Check if current user can edit target user
     * Rules:
     * - Student: Only their own profile
     * - Staff: Students and their own profile, NOT other Staff
     * - Admin: All users
     */
    public static function canEditUser(User $targetUser): bool
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            return false;
        }

        // Admin can edit anyone
        if ($currentUser->isAdmin()) {
            return true;
        }

        // User can edit their own profile
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Staff can edit Students only (not other Staff or Admin)
        if ($currentUser->isStaff() && $targetUser->isStudent()) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user can view user management section
     * Only Staff and Admin can access
     */
    public static function canAccessUserManagement(): bool
    {
        $user = Auth::user();
        return $user && ($user->isStaff() || $user->isAdmin());
    }

    /**
     * Check if current user can create new users
     * - Staff: Can create Students only
     * - Admin: Can create Students and Staff
     */
    public static function canCreateUser(): bool
    {
        $user = Auth::user();
        return $user && ($user->isStaff() || $user->isAdmin());
    }

    /**
     * Check if current user can create user with specific role
     * - Staff: Can only create Students
     * - Admin: Can create Students and Staff (not Admin)
     */
    public static function canCreateRole(string $role): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($role === 'Admin') {
            return false; // No one can create Admin
        }

        if ($role === 'Staff') {
            return $user->isAdmin(); // Only Admin can create Staff
        }

        if ($role === 'Student') {
            return $user->isStaff() || $user->isAdmin(); // Staff and Admin can create Students
        }

        return false;
    }

    /**
     * Check if current user can deactivate target user
     * Rules:
     * - Cannot deactivate yourself
     * - Staff: Can deactivate Students only
     * - Admin: Can deactivate Students and Staff
     */
    public static function canDeactivateUser(User $targetUser): bool
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            return false;
        }

        // Cannot deactivate yourself
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        // Cannot deactivate Admin
        if ($targetUser->isAdmin()) {
            return false;
        }

        // Admin can deactivate Students and Staff
        if ($currentUser->isAdmin()) {
            return true;
        }

        // Staff can deactivate Students only
        if ($currentUser->isStaff() && $targetUser->isStudent()) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user can view target user's profile
     */
    public static function canViewUser(User $targetUser): bool
    {
        $currentUser = Auth::user();

        if (!$currentUser) {
            return false;
        }

        // Admin can view anyone
        if ($currentUser->isAdmin()) {
            return true;
        }

        // User can view their own profile
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Staff can view Students only
        if ($currentUser->isStaff() && $targetUser->isStudent()) {
            return true;
        }

        return false;
    }

    /**
     * Get allowed roles for user creation based on current user role
     */
    public static function getAllowedRolesForCreation(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($user->isAdmin()) {
            return ['Student', 'Staff']; // Admin can create Student and Staff
        }

        if ($user->isStaff()) {
            return ['Student']; // Staff can only create Student
        }

        return [];
    }

    /**
     * Throw exception if user doesn't have permission
     */
    public static function authorize(string $action, string $resource): void
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('You must be logged in to access this resource.');
        }

        if ($action === 'create' && $resource === 'user') {
            if (!self::canCreateUser()) {
                throw new \Exception('You do not have permission to create users.');
            }
        } elseif ($action === 'view' && $resource === 'all_users') {
            if (!self::canAccessUserManagement()) {
                throw new \Exception('You do not have permission to access user management.');
            }
        } else {
            throw new \Exception('Unauthorized access.');
        }
    }
}
