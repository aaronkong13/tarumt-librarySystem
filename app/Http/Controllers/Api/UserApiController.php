<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\ReservationService;
use App\Services\FineService;
use App\Services\BorrowingService;
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
    private ReservationService $reservationService;
    private FineService $fineService;
    private BorrowingService $borrowingService;

    public function __construct(UserService $userService, ReservationService $reservationService, FineService $fineService, BorrowingService $borrowingService)
    {
        $this->userService = $userService;
        $this->reservationService = $reservationService;
        $this->fineService = $fineService;
        $this->borrowingService = $borrowingService;
    }

    /**
     * GET /api/users
     * Get all users with pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $users = $this->userService->getFilteredUsers($request, $perPage);

            // Process users data to handle BLOB fields
            $processedUsers = $users->items();
            foreach ($processedUsers as $user) {
                $this->processUserForApi($user);
            }

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Users retrieved successfully',
                'data' => [
                    'data' => $processedUsers,
                    'pagination' => [
                        'total' => $users->total(),
                        'per_page' => $users->perPage(),
                        'current_page' => $users->currentPage(),
                        'last_page' => $users->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'User not found',
                ], 404);
            }

            // Clear ALL output buffers to remove any BOM or whitespace
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            // Process user data to handle BLOB fields
            $this->processUserForApi($user);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'User retrieved successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                    'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'User updated successfully',
                'data' => $updated,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'User not found',
                ], 404);
            }

            $this->userService->deleteUser($id);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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

            // Process users data to return only id and name for dropdown
            $processedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            });

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => "Users with role '$role' retrieved successfully",
                'data' => $processedUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                    'timestamp' => now()->toIso8601String(),
                    'message' => 'Search query required',
                ], 400);
            }

            $users = $this->userService->searchUsers($query);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Search completed successfully',
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
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
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/{id}/reservations
     * Get user's reservations
     */
    public function getUserReservations($id): JsonResponse
    {
        try {
            $reservations = $this->reservationService->getUserReservations($id);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'User reservations retrieved successfully',
                'data' => $reservations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving user reservations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/users/{id}/fines
     * Get user's fines
     */
    public function getUserFines($id): JsonResponse
    {
        try {
            $fines = $this->fineService->getUserFinesWithStates($id);

            return response()->json([
                'success' => true,
                'timestamp' => now()->toIso8601String(),
                'message' => 'User fines retrieved successfully',
                'data' => $fines,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error retrieving user fines',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process user data for API response (handle BLOB fields)
     */
    private function processUserForApi(&$user)
    {
        if ($user && isset($user->profile_image) && $user->profile_image) {
            // Convert BLOB to base64 data URI
            $user->profile_image = 'data:image/jpeg;base64,' . base64_encode($user->profile_image);
        }
    }
}
