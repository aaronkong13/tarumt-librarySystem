<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Upgrade cover_image from BLOB (~64KB) to MEDIUMBLOB (~16MB)
        DB::statement('ALTER TABLE books MODIFY cover_image MEDIUMBLOB NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to BLOB
        DB::statement('ALTER TABLE books MODIFY cover_image BLOB NULL');
    }
};
