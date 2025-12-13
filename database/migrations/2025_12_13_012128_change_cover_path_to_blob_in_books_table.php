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
        Schema::table('books', function (Blueprint $table) {
            // Change cover_path from string to binary (BLOB)
            $table->binary('cover_image')->nullable()->after('category');
            $table->dropColumn('cover_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Restore original string column
            $table->string('cover_path')->nullable()->after('category');
            $table->dropColumn('cover_image');
        });
    }
};
