<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'borrowing_id',
        'amount',
        'reason',
        'status',
        'paid_date',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function borrowing()
    {
        return $this->belongsTo(Borrowing::class);
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper
    public function markAsPaid($paymentMethod = 'cash')
    {
        $this->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $paymentMethod,
        ]);
    }
}
