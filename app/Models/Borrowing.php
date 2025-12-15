<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borrowing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'book_id',
        'borrow_date',
        'due_date',
        'return_date',
        'status',
        'fine_amount',
        'fine_paid',
        'notes',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'due_date' => 'date',
        'return_date' => 'date',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
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

    public function fines()
    {
        return $this->hasMany(Fine::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'borrowed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'borrowed')->where('due_date', '<', now());
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isOverdue()
    {
        return $this->status === 'borrowed' && $this->due_date->isPast();
    }

    public function getDaysOverdue()
    {
        if (!$this->isOverdue()) return 0;
        return now()->diffInDays($this->due_date);
    }

    public function calculateFine($ratePerDay = 0.50)
    {
        return $this->getDaysOverdue() * $ratePerDay;
    }
}
