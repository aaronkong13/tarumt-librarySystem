<?php

// Test script for reservation functionality
// Run this with: php test-reservation.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Book;
use App\Services\ReservationService;

try {
    echo "=== Testing Reservation Module ===\n\n";
    
    // Find a student
    $student = User::where('role', 'Student')->first();
    if (!$student) {
        echo "❌ No student found in database\n";
        exit(1);
    }
    echo "✓ Found student: {$student->name} (ID: {$student->id})\n";
    
    // Find a borrowed book
    $book = Book::where('status', 'Borrowed')->first();
    if (!$book) {
        echo "❌ No borrowed books found. Please borrow a book first.\n";
        exit(1);
    }
    echo "✓ Found borrowed book: {$book->title} (ID: {$book->bookId})\n";
    
    // Test reservation service
    $service = new ReservationService();
    
    echo "\n--- Attempting to create reservation ---\n";
    
    try {
        $reservation = $service->reserveBook($student->id, $book->bookId);
        echo "✅ SUCCESS! Reservation created:\n";
        echo "   - Reservation ID: {$reservation->id}\n";
        echo "   - Queue Position: {$reservation->queue_position}\n";
        echo "   - Status: {$reservation->status}\n";
        echo "   - Created: {$reservation->created_at}\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR: {$e->getMessage()}\n";
        echo "\nStack trace:\n";
        echo $e->getTraceAsString();
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString();
}

echo "\n\n=== Test Complete ===\n";
