<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * UserApiController - REST API for User Module
 * 
 * Provides JSON endpoints for CRUD operations on users.
 * Can be consumed by mobile apps, frontend frameworks, or external services.
 */
class UserApiController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Clean binary data from user model for JSON serialization
     * Converts profile_image BLOB to base64 or removes it to prevent UTF-8 encoding errors
     */
    private function cleanUserData($users, $includeImage = true)
    {
        if ($users instanceof \Illuminate\Database\Eloquent\Collection) {
            return $users->map(function($user) use ($includeImage) {
                return $this->cleanSingleUser($user, $includeImage);
            });
        }

        if ($users instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $items = $users->items();
            $cleaned = array_map(function($user) use ($includeImage) {
                return $this->cleanSingleUser($user, $includeImage);
            }, $items);
            return $cleaned;
        }

        // Single user
        return $this->cleanSingleUser($users, $includeImage);
    }

    /**
     * Clean a single user object
     */
    private function cleanSingleUser($user, $includeImage = true)
    {
        if (is_object($user) && method_exists($user, 'toArray')) {
            if ($user->profile_image && $includeImage) {
                // Convert BLOB to base64 with data URI format (like BookApiController)
                $user->profile_image = 'data:image/jpeg;base64,' . base64_encode($user->profile_image);
            } else {
                // Remove binary data
                $user->profile_image = null;
            }
        }
        return $user;
    }

    /**
     * GET /api/users
     * Get all users with pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $users = $this->userService->getPaginatedUsers($perPage);

            // Clean user data and include profile images as base64 (like BookApiController)
            $cleanedUsers = $this->cleanUserData($users, true);

            return response()->json([
                'success' => true,
                'message' => 'Users retrieved successfully',
                'data' => $cleanedUsers,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/{id}
     * Get a single user by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Clean user data and include profile image as base64
            $cleanedUser = $this->cleanSingleUser($user, true);

            return response()->json([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => $cleanedUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/users
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'in:Student,Staff,Admin'],
            ]);

            // Hash password
            $validated['password'] = bcrypt($validated['password']);

            // Create user
            $user = $this->userService->createUser($validated);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/users/{id}
     * Update an existing user
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Validate input
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'email', 'unique:users,email,' . $id],
                'role' => ['sometimes', 'in:Student,Staff,Admin'],
            ]);

            // Update user
            $updated = $this->userService->updateUser($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $updated,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/users/{id}
     * Delete a user (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $this->userService->deleteUser($id);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/role/{role}
     * Get users by role
     */
    public function getByRole($role): JsonResponse
    {
        try {
            $users = $this->userService->getUsersByRole($role);

            // Clean user data and include profile images as base64
            $cleanedUsers = $this->cleanUserData($users, true);

            return response()->json([
                'success' => true,
                'message' => "Users with role '$role' retrieved successfully",
                'data' => $cleanedUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/search
     * Search users by name or email
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query('q');

            if (!$query) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query required',
                ], 400);
            }

            $users = $this->userService->searchUsers($query);

            // Clean user data and include profile images as base64
            $cleanedUsers = $this->cleanUserData($users, true);

            return response()->json([
                'success' => true,
                'message' => 'Search completed successfully',
                'data' => $cleanedUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/stats
     * Get user statistics
     */
    public function stats(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'User statistics retrieved successfully',
                'data' => [
                    'total_users' => $this->userService->getTotalUsers(),
                    'students' => count($this->userService->getUsersByRole('Student')),
                    'staff' => count($this->userService->getUsersByRole('Staff')),
                    'admins' => count($this->userService->getUsersByRole('Admin')),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
