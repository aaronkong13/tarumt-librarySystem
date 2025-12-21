<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * UserService - Data Access Layer for User Module (INTERNAL ACCESS)
 * 
 * This service layer handles all database operations for the User model.
 * UserController uses this service for DIRECT database access (internal module).
 * Other modules should use UserApiClient for HTTP API access (external).
 * 
 * ALL database queries should be executed in this service, not in controllers.
 */
class UserService
{
    /**
     * Get all users without filters
     * 
     * @return Collection
     */
    public function getAllUsers()
    {
        return User::all();
    }

    /**
     * Get users with pagination
     * 
     * @param int $perPage - Number of items per page
     * @return LengthAwarePaginator
     */
    public function getPaginatedUsers(int $perPage = 10)
    {
        return User::paginate($perPage);
    }

    /**
     * Get filtered users with pagination (used by UserController)
     * This is the main method for listing users with all filters
     * 
     * @param Request $request - HTTP request with filters
     * @param int $perPage - Items per page
     * @return LengthAwarePaginator
     */
    public function getFilteredUsers(Request $request, int $perPage = 10)
    {
        // Build query
        $query = User::withTrashed()
            ->where('role', '!=', 'Admin'); // Admin is hidden from list

        // Staff can only view Students (only if authenticated)
        if (Auth::check() && Auth::user()->isStaff()) {
            $query->where('role', 'Student');
        }

        // Apply search filter (name or email)
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Apply status filter (Active/Inactive)
        if ($request->filled('status')) {
            if ($request->status === 'Active') {
                $query->whereNull('deleted_at');
            } else {
                $query->whereNotNull('deleted_at');
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'name');
        if ($sortBy === 'name') {
            $query->orderBy('name', 'asc');
        } elseif ($sortBy === 'role') {
            $query->orderBy('role', 'asc');
        } elseif ($sortBy === 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        // Paginate results
        return $query->paginate($perPage);
    }

    /**
     * Get a single user by ID (with trashed)
     * 
     * @param int $id - User ID
     * @return User|null
     */
    public function getUserById($id)
    {
        return User::withTrashed()->find($id);
    }

    /**
     * Get users by role
     * 
     * @param string $role - User role (Admin, Staff, Student)
     * @return Collection
     */
    public function getUsersByRole($role)
    {
        return User::where('role', $role)->get();
    }

    /**
     * Get users by multiple IDs
     * 
     * @param array $ids - Array of user IDs
     * @return Collection
     */
    public function getUsersByIds(array $ids)
    {
        return User::whereIn('id', $ids)->get();
    }

    /**
     * Create a new user
     * 
     * @param array $data - User data (name, email, password, role)
     * @return User
     */
    public function createUser(array $data)
    {
        return User::create($data);
    }

    /**
     * Update an existing user
     * 
     * @param int $id - User ID
     * @param array $data - Fields to update
     * @return User|null
     */
    public function updateUser($id, array $data)
    {
        $user = User::withTrashed()->find($id);
        
        if ($user) {
            $user->update($data);
        }

        return $user;
    }

    /**
     * Soft delete a user (marks as deleted without removing from DB)
     * 
     * @param int $id - User ID
     * @return bool
     */
    public function deleteUser($id)
    {
        $user = User::find($id);
        
        if ($user) {
            return $user->delete();
        }

        return false;
    }

    /**
     * Restore a soft-deleted user
     * 
     * @param int $id - User ID
     * @return bool
     */
    public function restoreUser($id)
    {
        $user = User::withTrashed()->find($id);
        
        if ($user && $user->trashed()) {
            return $user->restore();
        }

        return false;
    }

    /**
     * Get total user count (excluding Admin)
     * 
     * @return int
     */
    public function getTotalUsers()
    {
        return User::where('role', '!=', 'Admin')->count();
    }

    /**
     * Get user count by role
     * 
     * @param string $role
     * @return int
     */
    public function getUserCountByRole($role)
    {
        return User::where('role', $role)->count();
    }

    /**
     * Get active user count
     * 
     * @return int
     */
    public function getActiveUsersCount()
    {
        return User::where('role', '!=', 'Admin')
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get inactive user count
     * 
     * @return int
     */
    public function getInactiveUsersCount()
    {
        return User::where('role', '!=', 'Admin')
            ->whereNotNull('deleted_at')
            ->count();
    }

    /**
     * Search users by name or email
     * 
     * @param string $query - Search keyword
     * @return Collection
     */
    public function searchUsers($query)
    {
        return User::where('name', 'LIKE', "%$query%")
            ->orWhere('email', 'LIKE', "%$query%")
            ->get();
    }

    /**
     * Get user statistics (for Dashboard and API)
     * 
     * @return array
     */
    public function getUserStats()
    {
        return [
            'total_users' => $this->getTotalUsers(),
            'total_students' => $this->getUserCountByRole('Student'),
            'total_staff' => $this->getUserCountByRole('Staff'),
            'active_users' => $this->getActiveUsersCount(),
            'inactive_users' => $this->getInactiveUsersCount(),
        ];
    }

    /**
     * Check if user exists
     * 
     * @param int $id
     * @return bool
     */
    public function userExists($id)
    {
        return User::where('id', $id)->exists();
    }

    /**
     * Check if email exists (for validation)
     * 
     * @param string $email
     * @param int|null $excludeId - Exclude this user ID (for updates)
     * @return bool
     */
    public function emailExists($email, $excludeId = null)
    {
        $query = User::where('email', $email);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
}
