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
}