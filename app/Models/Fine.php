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

    // Status constants
    const STATUS_UNPAID = 'unpaid';
    const STATUS_PAID = 'paid';
    const STATUS_WAIVED = 'waived';

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
        return $query->where('status', self::STATUS_UNPAID);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeWaived($query)
    {
        return $query->where('status', self::STATUS_WAIVED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Helper methods
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isUnpaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function isWaived(): bool
    {
        return $this->status === self::STATUS_WAIVED;
    }

    public function canBePaid(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function canBeWaived(): bool
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function markAsPaid($paymentMethod = 'cash')
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_date' => now(),
            'payment_method' => $paymentMethod,
        ]);
    }

    public function markAsWaived($reason = null)
    {
        $this->update([
            'status' => self::STATUS_WAIVED,
            'notes' => $reason ?? 'Fine waived',
        ]);
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return 'RM ' . number_format($this->amount, 2);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_UNPAID => 'danger',
            self::STATUS_PAID => 'success',
            self::STATUS_WAIVED => 'warning',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_WAIVED => 'Waived',
            default => 'Unknown',
        };
    }
}
