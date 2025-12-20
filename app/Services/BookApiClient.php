<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

/**
 * BookApiClient - Service for accessing Book module data via API
 * 
 * This enforces module boundaries: other modules should not directly
 * access BookService or Book model, but instead use this API client.
 * 
 * Uses API_URL config to allow separate frontend/backend on different ports.
 */
class BookApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        // Use API_URL config (allows frontend/backend separation on different ports)
        $apiUrl = config('app.api_url') ?? config('app.url');
        $this->baseUrl = $apiUrl . '/api/books';
    }

    /**
     * Get a book by ID
     */
    public function getBook(int $bookId)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/{$bookId}");

            if (!$response->successful()) {
                throw new Exception("Failed to fetch book {$bookId}: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("Book API error: " . $e->getMessage());
        }
    }

    /**
     * Get multiple books by IDs
     */
    public function getBooks(array $bookIds = null)
    {
        try {
            $url = $this->baseUrl;
            if ($bookIds) {
                $url .= '?' . http_build_query(['ids' => implode(',', $bookIds)]);
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch books: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("Book API error: " . $e->getMessage());
        }
    }

    /**
     * Update book status (for borrowing/returning)
     */
    public function updateBookStatus(int $bookId, string $status)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->put("{$this->baseUrl}/{$bookId}", [
                'status' => $status,
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to update book status: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("Book API error: " . $e->getMessage());
        }
    }

    /**
     * Check if book is available
     */
    public function isBookAvailable(int $bookId): bool
    {
        $book = $this->getBook($bookId);
        return $book['status'] === 'Available';
    }

    /**
     * Get book by status
     */
    public function getBooksByStatus(string $status)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get("{$this->baseUrl}/status/{$status}");

            if (!$response->successful()) {
                throw new Exception("Failed to fetch books by status: " . $response->body());
            }

            $data = $response->json();
            
            if (!$data['success']) {
                throw new Exception($data['message'] ?? 'Unknown error');
            }

            return $data['data'];
        } catch (Exception $e) {
            throw new Exception("Book API error: " . $e->getMessage());
        }
    }
}
