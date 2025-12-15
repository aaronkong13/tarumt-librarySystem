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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('book_id');
            $table->foreign('book_id')->references('bookId')->on('books')->onDelete('cascade');
            $table->date('reservation_date');
            $table->date('expiry_date');
            $table->enum('status', ['active', 'fulfilled', 'cancelled', 'expired'])->default('active');
            $table->integer('queue_position')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
