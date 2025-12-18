<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

/**
 * UserApiClient - Service for accessing User module data via API
 * 
 * This enforces module boundaries: other modules should not directly
 * access UserService or User model, but instead use this API client.
 * 
 * External modules (like Borrowing, Reservation) use this to access user data.
 */
class UserApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('app.url') . '/api/users';
    }

    /**
     * Get a user by ID
     * 
     * @param int $userId
     * @return array User data
     * @throws Exception
     */
    public function getUser(int $userId)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/{$userId}");

            if (!$response->successful()) {
                throw new Exception("Failed to fetch user {$userId}: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("User API error: " . $e->getMessage());
        }
    }

    /**
     * Get multiple users by IDs
     * 
     * @param array|null $userIds
     * @return array Users data
     * @throws Exception
     */
    public function getUsers(array $userIds = null)
    {
        try {
            $url = $this->baseUrl;
            if ($userIds) {
                $url .= '?' . http_build_query(['ids' => implode(',', $userIds)]);
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch users: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("User API error: " . $e->getMessage());
        }
    }

    /**
     * Get users by role (Student, Staff, Admin)
     * 
     * @param string $role
     * @return array Users data
     * @throws Exception
     */
    public function getUsersByRole(string $role)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/role/{$role}");

            if (!$response->successful()) {
                throw new Exception("Failed to fetch users by role: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("User API error: " . $e->getMessage());
        }
    }

    /**
     * Search users by name or email
     * 
     * @param string $query
     * @return array Users data
     * @throws Exception
     */
    public function searchUsers(string $query)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/search/query", [
                'q' => $query
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to search users: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("User API error: " . $e->getMessage());
        }
    }

    /**
     * Get user statistics
     * 
     * @return array Statistics data
     * @throws Exception
     */
    public function getUserStats()
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/stats/overview");

            if (!$response->successful()) {
                throw new Exception("Failed to fetch user stats: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("User API error: " . $e->getMessage());
        }
    }

    /**
     * Check if user exists and is active
     * 
     * @param int $userId
     * @return bool
     */
    public function isUserActive(int $userId): bool
    {
        try {
            $user = $this->getUser($userId);
            return isset($user['status']) && $user['status'] === 'Active';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate user has specific role
     * 
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function userHasRole(int $userId, string $role): bool
    {
        try {
            $user = $this->getUser($userId);
            return isset($user['role']) && $user['role'] === $role;
        } catch (Exception $e) {
            return false;
        }
    }
}
