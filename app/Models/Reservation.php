<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_WAITING = 'waiting';
    const STATUS_NOTIFIED = 'notified';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_ACTIVE = 'active'; // Legacy support

    protected $fillable = [
        'user_id',
        'book_id',
        'reservation_date',
        'expiry_date',
        'notified_at',
        'status',
        'queue_position',
        'notes',
    ];

    protected $casts = [
        'reservation_date' => 'datetime',
        'expiry_date' => 'datetime',
        'notified_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id', 'bookId');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_WAITING, self::STATUS_NOTIFIED, 'active']);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeNotified($query)
    {
        return $query->where('status', self::STATUS_NOTIFIED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_NOTIFIED)
            ->where('expiry_date', '<', now());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBook($query, $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    public function scopeOrderedQueue($query)
    {
        return $query->orderBy('queue_position')->orderBy('created_at');
    }

    // Helper methods
    public function isExpired()
    {
        return $this->status === self::STATUS_NOTIFIED 
            && $this->expiry_date 
            && $this->expiry_date->isPast();
    }

    public function isWaiting()
    {
        return $this->status === self::STATUS_WAITING;
    }

    public function isNotified()
    {
        return $this->status === self::STATUS_NOTIFIED;
    }

    public function getRemainingDays()
    {
        if (!$this->expiry_date) {
            return null;
        }
        
        $days = now()->diffInDays($this->expiry_date, false);
        return max(0, $days);
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            self::STATUS_WAITING => 'Waiting in Queue',
            self::STATUS_NOTIFIED => 'Available - Collect Now',
            self::STATUS_FULFILLED => 'Borrowed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            'active' => 'Active', // Legacy
            default => ucfirst($this->status),
        };
    }
}
