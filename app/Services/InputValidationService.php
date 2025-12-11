<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Input Validation Service
 * Handles validation of user inputs across the application
 */
class InputValidationService
{
    /**
     * Validate user registration data
     * 
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
     */
    public static function validateRegistration(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:Student,Staff,Admin',
            'phone' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]+$/',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Please select a role.',
            'role.in' => 'Invalid role selected. Must be Student, Staff, or Admin.',
            'phone.regex' => 'Please provide a valid phone number.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate user profile update data
     * 
     * @param array $data
     * @param int $userId Current user ID for unique email validation
     * @return array Validated data
     * @throws ValidationException
     */
    public static function validateProfileUpdate(array $data, int $userId): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
            'phone' => 'nullable|string|max:20|regex:/^[0-9\-\+\(\)\s]+$/',
            'address' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:8|confirmed',
        ], [
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already taken.',
            'phone.regex' => 'Please provide a valid phone number.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate password reset request
     * 
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
     */
    public static function validatePasswordReset(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required|string',
        ], [
            'email.required' => 'Email is required.',
            'email.exists' => 'This email is not registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate email for password reset request
     * 
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
     */
    public static function validatePasswordResetRequest(array $data): array
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email is required.',
            'email.exists' => 'We cannot find a user with that email address.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
