<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Implement Soft Delete

class Book extends Model
{
    use HasFactory, SoftDeletes;

    // 1. Define the table name (Optional, but good for clarity)
    protected $table = 'books';

    // Custom primary key matches migration definition
    protected $primaryKey = 'bookId';
    public $incrementing = true;
    protected $keyType = 'int';

    public function getRouteKeyName()
    {
        return 'bookId';
    }

    // 2. Allow Mass Assignment (CRITICAL)
    // This tells Laravel: "It is safe to save these fields automatically."
    // If you don't list a field here, Book::create() will ignore it.
    protected $fillable = [
        'title',
        'author',
        'isbn',
        'year',
        'category',
        'cover_image', // BLOB - stores actual image bytes
        'status',     // e.g., 'available', 'lost'
    ];

    // 3. Casts (Data Type Protection)
    protected $casts = [
        'year' => 'integer',
    ];
}