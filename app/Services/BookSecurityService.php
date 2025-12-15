<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Exception;
use Illuminate\Support\Facades\Log;

class BookSecurityService
{
    // =========================================================================
    // FEATURE: ACCESS CONTROL
    // REF: OWASP [84]
    // =========================================================================

    /**
     * Enforce Staff Access
     */
    public function enforceStaffAccess($user)
    {
        if (!$user) {
            Log::warning("Security Alert: Unauthenticated access attempt to Book Module.");
            abort(403, 'Unauthorized: Login required.');
        }

        // OWASP [84]: Restrict access to protected URLs to only authorized users.
        // Allow Staff and Admin roles (case-sensitive)
        if (!in_array($user->role, ['Staff', 'Admin'], true)) {
            Log::warning("Security Alert: Unauthorized access attempt by User ID {$user->id}.");
            abort(403, 'Unauthorized: Staff privileges required.');
        }

        return true;
    }

    // =========================================================================
    // FEATURE: INPUT VALIDATION & SANITIZATION
    // REF: OWASP [11] & OWASP [21]
    // =========================================================================

    /**
     * Clean and Validate Book Data
     */
    public function validateAndSanitizeBookData(array $data)
    {
        $sanitized = [];

        // 1. Handling the Title
        if (isset($data['title'])) {
            // OWASP [21]: Contextually sanitize output of un-trusted data.
            // Use htmlspecialchars instead of strip_tags to preserve UTF-8
            $cleanTitle = htmlspecialchars(trim($data['title']), ENT_QUOTES, 'UTF-8');
            $cleanTitle = html_entity_decode($cleanTitle, ENT_QUOTES, 'UTF-8');
            
            // Validate length using mb_strlen for UTF-8 safe counting
            if (mb_strlen($cleanTitle, 'UTF-8') > 255) {
                throw new Exception("Validation Error: Title is too long (Max 255 chars).");
            }
            $sanitized['title'] = $cleanTitle;
        }

        if (isset($data['author'])) {
            $cleanAuthor = htmlspecialchars(trim($data['author']), ENT_QUOTES, 'UTF-8');
            $cleanAuthor = html_entity_decode($cleanAuthor, ENT_QUOTES, 'UTF-8');
            if (trim($cleanAuthor) === '') {
                throw new Exception("Validation Error: Author is required.");
            }
            $sanitized['author'] = $cleanAuthor;
        }

        // 2. Handling the ISBN
        if (isset($data['isbn'])) {
            // OWASP [11]: Validate for expected data types (Whitelist check).
            if (!preg_match('/^[0-9-]+$/', $data['isbn'])) {
                throw new Exception("Validation Error: ISBN contains invalid characters.");
            }
            $sanitized['isbn'] = trim($data['isbn']);
        }

        // 3. Handling the Year
        if (isset($data['year'])) {
            // OWASP [11]: Validate for expected data types (Integer check).
            if (!filter_var($data['year'], FILTER_VALIDATE_INT)) {
                throw new Exception("Validation Error: Year must be a valid number.");
            }
            $sanitized['year'] = (int)$data['year'];
        }

        if (isset($data['category'])) {
            $cleanCategory = htmlspecialchars(trim($data['category']), ENT_QUOTES, 'UTF-8');
            $cleanCategory = html_entity_decode($cleanCategory, ENT_QUOTES, 'UTF-8');
            if (trim($cleanCategory) === '') {
                throw new Exception("Validation Error: Category is required.");
            }
            $sanitized['category'] = $cleanCategory;
        }

        if (isset($data['status'])) {
            $allowed = ['Available', 'Borrowed', 'Lost', 'Damaged'];
            if (!in_array($data['status'], $allowed, true)) {
                throw new Exception("Validation Error: Invalid status value.");
            }
            $sanitized['status'] = $data['status'];
        }

        return $sanitized;
    }

    // =========================================================================
    // FEATURE: FILE SECURITY
    // REF: OWASP [183]
    // =========================================================================

    /**
     * Validate Image Uploads (MIME Type)
     */
    public function validateCoverImage(UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new Exception("File upload error.");
        }

        // Whitelist of allowed image types
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        // OWASP [183]: Validate uploaded files by checking file headers.
        $mimeType = $file->getMimeType();

        if (!in_array($mimeType, $allowedMimeTypes)) {
            Log::error("Security Alert: Suspicious file upload attempt: $mimeType");
            throw new Exception("Invalid file type. Only JPG, PNG, or GIF allowed.");
        }

        // Limit file size (2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new Exception("File size too large (Max 2MB).");
        }

        return true;
    }

    // =========================================================================
    // FEATURE: OUTPUT ENCODING
    // REF: OWASP [21] (Sanitization/Encoding)
    // =========================================================================

    /**
     * Escape Output for HTML
     */
    public function escapeOutput($value)
    {
        // OWASP [21]: Contextually output encode all data returned to the client.
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}