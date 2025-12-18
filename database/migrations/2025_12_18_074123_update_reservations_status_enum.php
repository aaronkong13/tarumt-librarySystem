<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to modify enum in MySQL
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('waiting', 'notified', 'fulfilled', 'cancelled', 'expired', 'active') NOT NULL DEFAULT 'waiting'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('active', 'fulfilled', 'cancelled', 'expired') NOT NULL DEFAULT 'active'");
    }
};
