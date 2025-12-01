<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // 1. Primary Key (Custom name 'bookId')
            $table->id('bookId');

            // 2. Book Details
            $table->string('title');
            $table->string('author');
            $table->string('isbn');
            $table->integer('year');      // Needed for your Seeder
            $table->string('category');

            // 3. Status (Default to 'Available')
            $table->string('status')->default('Available');

            // 4. Cover Image (Nullable - user might not upload one yet)
            $table->string('cover_path')->nullable();

            // 5. Standard Timestamps (created_at, updated_at)
            $table->timestamps();

            // 6. Soft Deletes (deleted_at) - For archiving
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};