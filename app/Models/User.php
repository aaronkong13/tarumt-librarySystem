<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if user is an Admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    /**
     * Check if user is a Staff member
     */
    public function isStaff(): bool
    {
        return $this->role === 'Staff';
    }

    /**
     * Check if user is a Student
     */
    public function isStudent(): bool
    {
        return $this->role === 'Student';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    // Relationships
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function fines()
    {
        return $this->hasMany(Fine::class);
    }

    public function activeBorrowings()
    {
        return $this->hasMany(Borrowing::class)->where('status', 'borrowed');
    }

    public function activeReservations()
    {
        return $this->hasMany(Reservation::class)
            ->where('status', 'active')
            ->where('expiry_date', '>=', now());
    }

    public function unpaidFines()
    {
        return $this->hasMany(Fine::class)->where('status', 'unpaid');
    }

    public function getTotalUnpaidFines()
    {
        return $this->unpaidFines()->sum('amount');
    }
}

