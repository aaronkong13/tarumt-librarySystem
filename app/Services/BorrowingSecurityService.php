<?php

namespace App\Services;

use App\Models\User;
use App\Models\Borrowing;
use App\Models\Fine;
use App\Models\Reservation;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * BorrowingSecurityService - Secure Coding Practices for Borrowing Module
 *
 * Implements OWASP Security Checklist:
 * - [1] Input Validation
 * - [84] Access Control
 * - [107] Error Handling and Logging
 * - [131] Data Protection
 */
class BorrowingSecurityService
{
    // =========================================================================
    // FEATURE: ACCESS CONTROL
    // REF: OWASP [84]
    // =========================================================================

    /**
     * Enforce Staff Access for privileged operations
     * OWASP [84]: Restrict access to protected URLs to only authorized users
     */
    public function enforceStaffAccess($user)
    {
        if (!$user) {
            Log::warning("Security Alert: Unauthenticated access attempt to Borrowing Module.");
            abort(403, 'Unauthorized: Login required.');
        }

        // OWASP [84]: Check role-based access
        if (!in_array($user->role, ['Staff', 'Admin'], true)) {
            Log::warning("Security Alert: Unauthorized borrowing access by User ID {$user->id}.", [
                'user_id' => $user->id,
                'role' => $user->role,
                'action' => 'borrowing_access_attempt'
            ]);
            abort(403, 'Unauthorized: Staff privileges required.');
        }

        return true;
    }

    /**
     * Verify user owns the resource (borrowing/fine/reservation)
     * OWASP [131]: Verify authorization to access data
     */
    public function enforceOwnership($user, $resource)
    {
        if (!$user) {
            Log::warning("Security Alert: Unauthenticated ownership check.");
            abort(403, 'Unauthorized: Login required.');
        }

        // Staff and Admin can access all resources
        if (in_array($user->role, ['Staff', 'Admin'], true)) {
            return true;
        }

        // Check if user owns the resource
        $userId = $user->id;
        $resourceUserId = null;

        if ($resource instanceof Borrowing || $resource instanceof Fine || $resource instanceof Reservation) {
            $resourceUserId = $resource->user_id;
        }

        if ($userId !== $resourceUserId) {
            Log::warning("Security Alert: Unauthorized data access attempt.", [
                'user_id' => $userId,
                'resource_type' => get_class($resource),
                'resource_id' => $resource->id ?? 'unknown',
                'action' => 'ownership_violation'
            ]);
            abort(403, 'Unauthorized: You can only access your own records.');
        }

        return true;
    }

    // =========================================================================
    // FEATURE: INPUT VALIDATION & SANITIZATION
    // REF: OWASP [1] & [11]
    // =========================================================================

    /**
     * Validate and sanitize borrowing input data
     * OWASP [1]: Validate input from all data sources
     * OWASP [11]: Validate for expected data types
     */
    public function validateBorrowingInput(array $data): array
    {
        $sanitized = [];

        // Validate book_id
        if (isset($data['book_id'])) {
            if (!filter_var($data['book_id'], FILTER_VALIDATE_INT) || $data['book_id'] <= 0) {
                Log::warning("Security Alert: Invalid book_id in borrowing input.", [
                    'book_id' => $data['book_id'],
                    'action' => 'input_validation_failed'
                ]);
                throw new Exception("Validation Error: Invalid book ID.");
            }
            $sanitized['book_id'] = (int)$data['book_id'];
        }

        // Validate user_id
        if (isset($data['user_id'])) {
            if (!filter_var($data['user_id'], FILTER_VALIDATE_INT) || $data['user_id'] <= 0) {
                Log::warning("Security Alert: Invalid user_id in borrowing input.", [
                    'user_id' => $data['user_id'],
                    'action' => 'input_validation_failed'
                ]);
                throw new Exception("Validation Error: Invalid user ID.");
            }
            $sanitized['user_id'] = (int)$data['user_id'];
        }

        // Validate duration_days
        if (isset($data['duration_days'])) {
            if (!filter_var($data['duration_days'], FILTER_VALIDATE_INT) ||
                $data['duration_days'] < 1 ||
                $data['duration_days'] > 30) {
                Log::warning("Security Alert: Invalid duration_days in borrowing input.", [
                    'duration_days' => $data['duration_days'],
                    'action' => 'input_validation_failed'
                ]);
                throw new Exception("Validation Error: Duration must be between 1 and 30 days.");
            }
            $sanitized['duration_days'] = (int)$data['duration_days'];
        }

        return $sanitized;
    }

    /**
     * Validate fine payment input
     * OWASP [1]: Validate input from all data sources
     */
    public function validateFinePaymentInput(array $data): array
    {
        $sanitized = [];

        if (isset($data['payment_method'])) {
            $allowedMethods = ['cash', 'card', 'online'];
            $method = strtolower(trim($data['payment_method']));

            if (!in_array($method, $allowedMethods, true)) {
                Log::warning("Security Alert: Invalid payment method.", [
                    'payment_method' => $data['payment_method'],
                    'action' => 'input_validation_failed'
                ]);
                throw new Exception("Validation Error: Invalid payment method.");
            }
            $sanitized['payment_method'] = $method;
        }

        return $sanitized;
    }

    /**
     * Validate reservation input
     * OWASP [11]: Validate for expected data types
     */
    public function validateReservationInput(array $data): array
    {
        $sanitized = [];

        if (isset($data['book_id'])) {
            if (!filter_var($data['book_id'], FILTER_VALIDATE_INT) || $data['book_id'] <= 0) {
                throw new Exception("Validation Error: Invalid book ID.");
            }
            $sanitized['book_id'] = (int)$data['book_id'];
        }

        if (isset($data['expiry_days'])) {
            if (!filter_var($data['expiry_days'], FILTER_VALIDATE_INT) ||
                $data['expiry_days'] < 1 ||
                $data['expiry_days'] > 7) {
                throw new Exception("Validation Error: Expiry days must be between 1 and 7.");
            }
            $sanitized['expiry_days'] = (int)$data['expiry_days'];
        }

        return $sanitized;
    }

    /**
     * Sanitize text input to prevent XSS
     * OWASP [21]: Contextually sanitize output of un-trusted data
     */
    public function sanitizeTextInput(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove HTML tags and encode special characters
        $clean = htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        $clean = html_entity_decode($clean, ENT_QUOTES, 'UTF-8');

        return $clean;
    }

    // =========================================================================
    // FEATURE: DATA PROTECTION
    // REF: OWASP [131]
    // =========================================================================

    /**
     * Mask sensitive user data in logs
     * OWASP [131]: Protect sensitive data
     */
    public function maskSensitiveData(array $data): array
    {
        $masked = $data;

        // Mask email if present
        if (isset($masked['email'])) {
            $email = $masked['email'];
            $parts = explode('@', $email);
            if (count($parts) === 2) {
                $masked['email'] = substr($parts[0], 0, 2) . '***@' . $parts[1];
            }
        }

        // Mask phone if present
        if (isset($masked['phone'])) {
            $phone = $masked['phone'];
            $masked['phone'] = '***' . substr($phone, -4);
        }

        return $masked;
    }

    /**
     * Encrypt sensitive data before storage
     * OWASP [131]: Encrypt sensitive data at rest
     */
    public function encryptSensitiveData(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }

        try {
            return encrypt($data);
        } catch (Exception $e) {
            Log::error("Encryption Error: Failed to encrypt sensitive data.", [
                'error' => $e->getMessage()
            ]);
            throw new Exception("Data protection error occurred.");
        }
    }

    /**
     * Decrypt sensitive data
     * OWASP [131]: Securely handle encrypted data
     */
    public function decryptSensitiveData(?string $encryptedData): ?string
    {
        if ($encryptedData === null || $encryptedData === '') {
            return null;
        }

        try {
            return decrypt($encryptedData);
        } catch (Exception $e) {
            Log::error("Decryption Error: Failed to decrypt sensitive data.", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // =========================================================================
    // FEATURE: ERROR HANDLING AND LOGGING
    // REF: OWASP [107]
    // =========================================================================

    /**
     * Log security events with context
     * OWASP [107]: Log all security-related events
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $maskedContext = $this->maskSensitiveData($context);

        Log::warning("Security Event: {$event}", array_merge([
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $maskedContext));
    }

    /**
     * Log borrowing activity
     * OWASP [107]: Maintain audit trails
     */
    public function logBorrowingActivity(string $action, int $userId, int $bookId, array $details = []): void
    {
        Log::info("Borrowing Activity: {$action}", [
            'action' => $action,
            'user_id' => $userId,
            'book_id' => $bookId,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'details' => $details
        ]);
    }

    /**
     * Handle and log exceptions safely
     * OWASP [107]: Do not log sensitive information in error messages
     */
    public function handleSecureException(Exception $e, string $userMessage = 'An error occurred'): Exception
    {
        // Log full error details for debugging (server-side only)
        Log::error("Borrowing Module Error", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // Return user-friendly message (no sensitive data)
        return new Exception($userMessage);
    }

    // =========================================================================
    // FEATURE: RATE LIMITING & ABUSE PREVENTION
    // REF: OWASP [45] & [191]
    // =========================================================================

    /**
     * Check for suspicious borrowing patterns
     * OWASP [191]: Detect automated attacks
     */
    public function detectSuspiciousActivity(int $userId): bool
    {
        $recentBorrowings = Borrowing::where('user_id', $userId)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();

        // More than 5 borrowing attempts in 5 minutes is suspicious
        if ($recentBorrowings > 5) {
            $this->logSecurityEvent('Suspicious borrowing activity detected', [
                'user_id' => $userId,
                'attempt_count' => $recentBorrowings
            ]);
            return true;
        }

        return false;
    }

    /**
     * Validate borrowing limits to prevent abuse
     * OWASP [45]: Prevent automated attacks
     */
    public function validateBorrowingLimits(User $user): bool
    {
        $activeBorrowings = $user->activeBorrowings()->count();
        $maxLimit = $user->isStudent() ? 3 : 5;

        if ($activeBorrowings >= $maxLimit) {
            Log::info("Borrowing limit reached", [
                'user_id' => $user->id,
                'active_borrowings' => $activeBorrowings,
                'max_limit' => $maxLimit
            ]);
            return false;
        }

        return true;
    }

    // =========================================================================
    // FEATURE: OUTPUT ENCODING
    // REF: OWASP [21]
    // =========================================================================

    /**
     * Escape output for HTML context
     * OWASP [21]: Contextually output encode all data returned to client
     */
    public function escapeOutput($value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
