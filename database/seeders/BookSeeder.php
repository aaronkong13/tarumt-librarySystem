<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Technology Book
        Book::create([
            'title' => 'The Clean Coder',
            'author' => 'Robert C. Martin',
            'isbn' => '978-0137081073',
            'year' => 2011,
            'category' => 'Technology',
            'status' => 'Available',
        ]);

        // 2. Fiction Book (Borrowed)
        Book::create([
            'title' => 'Harry Potter and the Sorcerer\'s Stone',
            'author' => 'J.K. Rowling',
            'isbn' => '978-0590353427',
            'year' => 1997,
            'category' => 'Fiction',
            'status' => 'Borrowed',
        ]);

        // 3. Education Book
        Book::create([
            'title' => 'Calculus: Early Transcendentals',
            'author' => 'James Stewart',
            'isbn' => '978-1285741550',
            'year' => 2015,
            'category' => 'Education',
            'status' => 'Available',
        ]);

        // 4. History Book (Lost)
        Book::create([
            'title' => 'Sapiens: A Brief History of Humankind',
            'author' => 'Yuval Noah Harari',
            'isbn' => '978-0062316097',
            'year' => 2014,
            'category' => 'History',
            'status' => 'Available',
        ]);

        // 5. Tech Reference
        Book::create([
            'title' => 'Introduction to Algorithms',
            'author' => 'Thomas H. Cormen',
            'isbn' => '978-0262033848',
            'year' => 2009,
            'category' => 'Technology',
            'status' => 'Available',
        ]);
    }
}