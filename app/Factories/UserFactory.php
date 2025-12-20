<?php

namespace App\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Factory Pattern Implementation for User Creation
 * This factory handles the creation of different types of users (Student and Staff)
 */
class UserFactory
{
    /**
     * Create a new user based on role
     * 
     * @param array $data User data including role
     * @return User
     */
    public static function create(array $data): User
    {
        // Validate and set default role
        $role = $data['role'] ?? 'Student';
        
        // Create user with hashed password
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => 'Active',
            'profile_image' => $data['profile_image'] ?? null,
        ]);
    }

    /**
     * Create a Student user
     * 
     * @param array $data User data
     * @return User
     */
    public static function createStudent(array $data): User
    {
        $data['role'] = 'Student';
        return self::create($data);
    }

    /**
     * Create a Staff user
     * 
     * @param array $data User data
     * @return User
     */
    public static function createStaff(array $data): User
    {
        $data['role'] = 'Staff';
        return self::create($data);
    }

    /**
     * Create an Admin user
     * 
     * @param array $data User data
     * @return User
     */
    public static function createAdmin(array $data): User
    {
        $data['role'] = 'Admin';
        return self::create($data);
    }

    /**
     * Update user information
     * 
     * @param User $user
     * @param array $data
     * @return User
     */
    public static function update(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
        ];

        // Update role if provided
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }

        // Only update password if provided
        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        // Update profile image if provided
        if (isset($data['profile_image'])) {
            $updateData['profile_image'] = $data['profile_image'];
        }

        $user->update($updateData);
        return $user->fresh();
    }

    /**
     * Deactivate a user (soft delete)
     * 
     * @param User $user
     * @return bool
     */
    public static function deactivate(User $user): bool
    {
        $user->status = 'Inactive';
        $user->save();
        return $user->delete(); // Soft delete
    }

    /**
     * Activate a user
     * 
     * @param User $user
     * @return User
     */
    public static function activate(User $user): User
    {
        $user->status = 'Active';
        $user->save();
        return $user;
    }
}
