<?php

namespace App\Services;

use App\Models\User;
use App\Models\Fine;
use App\Models\Borrowing;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * FineSecurityService - Secure Coding Practices for Fine Module
 *
 * Implements OWASP Security Checklist:
 * - [1] Input Validation
 * - [84] Access Control
 * - [107] Error Handling and Logging
 * - [131] Data Protection
 * 
 * Architecture mirrors BorrowingSecurityService for consistency.
 */
class FineSecurityService
{
    // =========================================================================
    // FEATURE: ACCESS CONTROL
    // REF: OWASP [84]
    // =========================================================================

    /**
     * Enforce Staff Access for privileged operations
     * OWASP [84]: Restrict access to protected URLs to only authorized users
     */
    public function enforceStaffAccess($user): bool
    {
        if (!$user) {
            Log::warning("Security Alert: Unauthenticated access attempt to Fine Module.");
            abort(403, 'Unauthorized: Login required.');
        }

        // OWASP [84]: Check role-based access
        if (!in_array($user->role, ['Staff', 'Admin'], true)) {
            Log::warning("Security Alert: Unauthorized fine management access by User ID {$user->id}.", [
                'user_id' => $user->id,
                'role' => $user->role,
                'action' => 'fine_management_access_attempt'
            ]);
            abort(403, 'Unauthorized: Staff privileges required.');
        }

        return true;
    }

    /**
     * Verify user owns the fine resource
     * OWASP [131]: Verify authorization to access data
     */
    public function enforceOwnership($user, Fine $fine): bool
    {
        if (!$user) {
            Log::warning("Security Alert: Unauthenticated ownership check for fine.");
            abort(403, 'Unauthorized: Login required.');
        }

        // Staff and Admin can access all fines
        if (in_array($user->role, ['Staff', 'Admin'], true)) {
            return true;
        }

        // Check if user owns the fine
        if ($user->id !== $fine->user_id) {
            Log::warning("Security Alert: Unauthorized fine access attempt.", [
                'user_id' => $user->id,
                'fine_id' => $fine->id,
                'fine_owner_id' => $fine->user_id,
                'action' => 'fine_ownership_violation'
            ]);
            abort(403, 'Unauthorized: You can only access your own fines.');
        }

        return true;
    }

    /**
     * Authorize payment action
     * OWASP [84]: Ensure staff can pay on behalf; students only their own
     */
    public function authorizePayment($user, Fine $fine): bool
    {
        if (!$user) {
            abort(403, 'Unauthorized: Login required.');
        }

        // Staff/Admin can pay any fine
        if (in_array($user->role, ['Staff', 'Admin'], true)) {
            Log::info("Fine payment authorized by staff", [
                'staff_id' => $user->id,
                'fine_id' => $fine->id,
                'fine_owner_id' => $fine->user_id
            ]);
            return true;
        }

        // Students can only pay their own fines
        if ($user->id !== $fine->user_id) {
            Log::warning("Security Alert: Unauthorized fine payment attempt.", [
                'user_id' => $user->id,
                'fine_id' => $fine->id,
                'fine_owner_id' => $fine->user_id,
                'action' => 'payment_authorization_failed'
            ]);
            abort(403, 'Unauthorized: You can only pay your own fines.');
        }

        return true;
    }

    /**
     * Authorize waive action (Staff/Admin only)
     * OWASP [84]: Restrict sensitive operations to authorized roles
     */
    public function authorizeWaive($user): bool
    {
        if (!$user) {
            abort(403, 'Unauthorized: Login required.');
        }

        if (!in_array($user->role, ['Staff', 'Admin'], true)) {
            Log::warning("Security Alert: Unauthorized fine waive attempt.", [
                'user_id' => $user->id,
                'role' => $user->role,
                'action' => 'waive_authorization_failed'
            ]);
            abort(403, 'Unauthorized: Only staff can waive fines.');
        }

        return true;
    }

    // =========================================================================
    // FEATURE: INPUT VALIDATION & SANITIZATION
    // REF: OWASP [1] & [11]
    // =========================================================================

    /**
     * Validate fine payment input data
     * OWASP [1]: Validate input from all data sources
     * OWASP [11]: Validate for expected data types
     */
    public function validatePaymentInput(array $data): array
    {
        $sanitized = [];

        // Validate fine_id
        if (isset($data['fine_id'])) {
            if (!filter_var($data['fine_id'], FILTER_VALIDATE_INT) || $data['fine_id'] <= 0) {
                Log::warning("Security Alert: Invalid fine_id in payment input.", [
                    'fine_id' => $data['fine_id'],
                    'action' => 'input_validation_failed'
                ]);
                throw new Exception("Validation Error: Invalid fine ID.");
            }
            $sanitized['fine_id'] = (int)$data['fine_id'];
        }

        // Validate payment_method
        if (isset($data['payment_method'])) {
            $allowedMethods = ['cash', 'card', 'online'];
            $method = strtolower(trim($data['payment_method']));

            if (!in_array($method, $allowedMethods, true)) {
                Log::warning("Security Alert: Invalid payment method in fine payment.", [
                    'payment_method' => $data['payment_method'],
                    'action' => 'input_validation_failed'
                ]);
                throw new Exception("Validation Error: Invalid payment method. Allowed: cash, card, online.");
            }
            $sanitized['payment_method'] = $method;
        }

        // Validate notes (optional)
        if (isset($data['notes'])) {
            $sanitized['notes'] = $this->sanitizeTextInput($data['notes']);
            if (strlen($sanitized['notes']) > 500) {
                throw new Exception("Validation Error: Notes cannot exceed 500 characters.");
            }
        }

        return $sanitized;
    }

    /**
     * Validate fine waive input data
     * OWASP [1]: Validate input from all data sources
     */
    public function validateWaiveInput(array $data): array
    {
        $sanitized = [];

        // Validate fine_id
        if (isset($data['fine_id'])) {
            if (!filter_var($data['fine_id'], FILTER_VALIDATE_INT) || $data['fine_id'] <= 0) {
                throw new Exception("Validation Error: Invalid fine ID.");
            }
            $sanitized['fine_id'] = (int)$data['fine_id'];
        }

        // Validate reason (required for waiving)
        if (isset($data['reason'])) {
            $sanitized['reason'] = $this->sanitizeTextInput($data['reason']);
            if (strlen($sanitized['reason']) > 500) {
                throw new Exception("Validation Error: Reason cannot exceed 500 characters.");
            }
        }

        return $sanitized;
    }

    /**
     * Validate fine amount
     * OWASP [11]: Validate for expected data types and ranges
     */
    public function validateFineAmount($amount): float
    {
        if (!is_numeric($amount)) {
            Log::warning("Security Alert: Non-numeric fine amount.", [
                'amount' => $amount,
                'action' => 'input_validation_failed'
            ]);
            throw new Exception("Validation Error: Fine amount must be numeric.");
        }

        $amount = (float)$amount;

        if ($amount < 0) {
            throw new Exception("Validation Error: Fine amount cannot be negative.");
        }

        if ($amount > 1000) {
            Log::warning("Security Alert: Suspiciously high fine amount.", [
                'amount' => $amount,
                'action' => 'high_amount_alert'
            ]);
            throw new Exception("Validation Error: Fine amount exceeds maximum allowed (RM 1000).");
        }

        return round($amount, 2);
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
        $clean = strip_tags(trim($input));
        $clean = htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');

        return $clean;
    }

    // =========================================================================
    // FEATURE: DATA PROTECTION
    // REF: OWASP [131]
    // =========================================================================

    /**
     * Mask sensitive user data in logs and responses
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

        // Mask user name (partial)
        if (isset($masked['name'])) {
            $name = $masked['name'];
            $masked['name'] = substr($name, 0, 2) . '***';
        }

        return $masked;
    }

    /**
     * Prepare fine data for public API response
     * OWASP [131]: Only expose necessary data
     */
    public function prepareFineForResponse(Fine $fine, bool $includeUser = false): array
    {
        $response = [
            'id' => $fine->id,
            'amount' => number_format($fine->amount, 2),
            'reason' => $fine->reason,
            'status' => $fine->status,
            'created_at' => $fine->created_at->toISOString(),
        ];

        if ($fine->status === 'paid') {
            $response['paid_date'] = $fine->paid_date?->toISOString();
            $response['payment_method'] = $fine->payment_method;
        }

        // Only include user data if authorized
        if ($includeUser && $fine->user) {
            $response['user'] = [
                'id' => $fine->user->id,
                'name' => $fine->user->name,
                // Don't expose email/phone in API responses
            ];
        }

        return $response;
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

        Log::warning("Fine Security Event: {$event}", array_merge([
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $maskedContext));
    }

    /**
     * Log fine activity with audit trail
     * OWASP [107]: Maintain audit trails for all transactions
     */
    public function logFineActivity(string $action, Fine $fine, array $details = []): void
    {
        Log::info("Fine Activity: {$action}", [
            'action' => $action,
            'fine_id' => $fine->id,
            'user_id' => $fine->user_id,
            'amount' => $fine->amount,
            'status' => $fine->status,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'details' => $details
        ]);
    }

    /**
     * Log payment transaction
     * OWASP [107]: Audit trail for financial transactions
     */
    public function logPaymentTransaction(Fine $fine, string $paymentMethod, $processedBy): void
    {
        Log::info("Fine Payment Transaction", [
            'transaction_type' => 'fine_payment',
            'fine_id' => $fine->id,
            'fine_owner_id' => $fine->user_id,
            'amount' => $fine->amount,
            'payment_method' => $paymentMethod,
            'processed_by' => $processedBy,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle and log exceptions safely
     * OWASP [107]: Do not log sensitive information in error messages
     */
    public function handleSecureException(Exception $e, string $userMessage = 'An error occurred'): Exception
    {
        // Log full error details for debugging (server-side only)
        Log::error("Fine Module Error", [
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
     * Detect suspicious fine payment patterns
     * OWASP [191]: Detect automated attacks or fraud
     */
    public function detectSuspiciousPaymentActivity(int $userId): bool
    {
        $recentPayments = Fine::where('user_id', $userId)
            ->where('status', 'paid')
            ->where('paid_date', '>', now()->subMinutes(5))
            ->count();

        // More than 10 payments in 5 minutes is suspicious
        if ($recentPayments > 10) {
            $this->logSecurityEvent('Suspicious fine payment activity detected', [
                'user_id' => $userId,
                'payment_count' => $recentPayments,
                'action' => 'rate_limit_exceeded'
            ]);
            return true;
        }

        return false;
    }

    /**
     * Validate fine can be modified (not already paid/waived)
     * OWASP [45]: Prevent invalid state modifications
     */
    public function validateFineCanBeModified(Fine $fine): bool
    {
        if ($fine->status !== 'unpaid') {
            Log::warning("Attempted modification of non-unpaid fine.", [
                'fine_id' => $fine->id,
                'current_status' => $fine->status,
                'action' => 'invalid_modification_attempt'
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

    /**
     * Format fine amount for display
     * OWASP [21]: Safe output formatting
     */
    public function formatAmountForDisplay(float $amount): string
    {
        return 'RM ' . number_format($amount, 2);
    }
}
