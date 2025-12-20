<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Book Model - Represents a physical book in the library system
 * 
 * This model tracks all book information including:
 * - Basic details (title, author, ISBN, year, category)
 * - Availability status (Available, Borrowed, Lost, Damaged)
 * - Cover image for display in UI
 * - Relationships with borrowings and reservations
 * 
 * Uses soft deletes so books are never permanently deleted from the system.
 */
class Book extends Model
{
    use HasFactory, SoftDeletes;

    // ═══════════════════════════════════════════════════════════════
    // TABLE CONFIGURATION
    // ═══════════════════════════════════════════════════════════════
    
    protected $table = 'books';
    protected $primaryKey = 'bookId';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * Get the route key name for model binding
     */
    public function getRouteKeyName()
    {
        return 'bookId';
    }

    // ═══════════════════════════════════════════════════════════════
    // MASS ASSIGNMENT PROTECTION
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Fields that can be mass assigned
     * @var array
     */
    protected $fillable = [
        'title',           // Book title
        'author',          // Author name
        'isbn',            // International Standard Book Number (unique identifier)
        'year',            // Publication year
        'category',        // Subject category (CS, IT, Engineering, etc)
        'cover_image',     // Book cover image (stored as BLOB)
        'status',          // Availability status (Available, Borrowed, Lost, Damaged)
    ];

    // ═══════════════════════════════════════════════════════════════
    // TYPE CASTING
    // ═══════════════════════════════════════════════════════════════
    
    /**
     * Automatic type casting for database columns
     * @var array
     */
    protected $casts = [
        'year' => 'integer',  // Ensure year is always numeric
    ];

    // ═══════════════════════════════════════════════════════════════
    // RELATIONSHIPS - How books connect to other entities
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all borrowing records for this book
     * One book can be borrowed many times over its lifetime
     */
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'book_id', 'bookId');
    }

    /**
     * Get all reservation records for this book
     * Users can queue up to borrow a book that's currently borrowed
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'book_id', 'bookId');
    }

    /**
     * Get the current active borrowing (if any)
     * Returns null if book is currently available
     */
    public function activeBorrowing()
    {
        return $this->hasOne(Borrowing::class, 'book_id', 'bookId')
            ->where('status', 'borrowed')
            ->latest();
    }

    /**
     * Get active reservations for this book
     * Only returns reservations that haven't expired
     */
    public function activeReservations()
    {
        return $this->hasMany(Reservation::class, 'book_id', 'bookId')
            ->where('status', 'active')
            ->where('expiry_date', '>=', now());
    }

    // ═══════════════════════════════════════════════════════════════
    // BUSINESS LOGIC - Methods to check book status
    // ═══════════════════════════════════════════════════════════════

    /**
     * Check if this book is available for borrowing
     * A book is available if its status is 'Available' AND it's not currently borrowed
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === 'Available' && !$this->activeBorrowing()->exists();
    }

    /**
     * Check if this book is currently borrowed by someone
     * 
     * @return bool
     */
    public function isBorrowed(): bool
    {
        return $this->activeBorrowing()->exists();
    }

    /**
     * Get a human-readable status display string
     * 
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'Available' => '✓ Available',
            'Borrowed' => '📤 Borrowed',
            'Lost' => '❌ Lost',
            'Damaged' => '⚠️ Damaged',
            default => 'Unknown'
        };
    }
}
