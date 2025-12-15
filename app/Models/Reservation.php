<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'book_id',
        'reservation_date',
        'expiry_date',
        'status',
        'queue_position',
        'notes',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'expiry_date' => 'date',
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
        return $query->where('status', 'active')->where('expiry_date', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')->where('expiry_date', '<', now());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBook($query, $bookId)
    {
        return $query->where('book_id', $bookId);
    }

    // Helper
    public function isExpired()
    {
        return $this->status === 'active' && $this->expiry_date->isPast();
    }
}
