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
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('borrowing_id')->constrained('borrowings')->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->string('reason')->default('Overdue return');
            $table->enum('status', ['unpaid', 'paid', 'waived'])->default('unpaid');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
