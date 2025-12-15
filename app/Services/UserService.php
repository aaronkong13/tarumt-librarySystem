<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\Paginator;

/**
 * UserService - Data Access Layer for User Module
 * 
 * This service layer handles all database operations for the User model.
 * Provides methods for managing users (CRUD operations).
 */
class UserService
{
    /**
     * Get all users without filters
     */
    public function getAllUsers()
    {
        return User::all();
    }

    /**
     * Get users with pagination
     * 
     * @param int $perPage - Number of items per page
     * @return Paginator
     */
    public function getPaginatedUsers(int $perPage = 10)
    {
        return User::paginate($perPage);
    }

    /**
     * Get a single user by ID
     * 
     * @param int $id - User ID
     * @return User|null
     */
    public function getUserById($id)
    {
        return User::find($id);
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
        $user = User::find($id);
        
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
     * Get total user count
     * 
     * @return int
     */
    public function getTotalUsers()
    {
        return User::count();
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
}
