<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Implement Soft Delete for not direct delete the record 

class Book extends Model
{
    use HasFactory, SoftDeletes;

    //Table Configuration
    protected $table = 'books';

    //Custom primary key matches migration definition
    protected $primaryKey = 'bookId';
    public $incrementing = true;
    protected $keyType = 'int';

    public function getRouteKeyName()
    {
        return 'bookId';
    }

    //Fillable Fields (Mass Assignment Protection)
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'year',
        'category',
        'cover_image', 
        'status',     // e.g., 'available', 'lost'
    ];

    //Casts (Data Type Protection)
    protected $casts = [
        'year' => 'integer',
    ];

    // Relationships
    public function borrowings()
    {
        return $this->hasMany(Borrowing::class, 'book_id', 'bookId');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'book_id', 'bookId');
    }

    public function activeBorrowing()
    {
        return $this->hasOne(Borrowing::class, 'book_id', 'bookId')
            ->where('status', 'borrowed')
            ->latest();
    }

    public function activeReservations()
    {
        return $this->hasMany(Reservation::class, 'book_id', 'bookId')
            ->where('status', 'active')
            ->where('expiry_date', '>=', now());
    }

    // Helper methods
    public function isAvailable()
    {
        return $this->status === 'Available' && !$this->activeBorrowing;
    }

    public function isBorrowed()
    {
        return $this->activeBorrowing()->exists();
    }
}
