<?php

namespace App\Http\Controllers;

use App\Services\BorrowingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * BorrowingController - Web Controller for Borrowing Module
 *
 * Architecture matches BookController:
 * - Uses BorrowingService for all business logic
 * - Controller only handles HTTP concerns (request/response)
 * - All State Pattern and Observer Pattern logic is in the service layer
 */
class BorrowingController extends Controller
{
    private BorrowingService $borrowingService;

    public function __construct()
    {
        // Inject service - same pattern as BookController
        $this->borrowingService = new BorrowingService();
    }

    /**
     * Show borrowing page with state information
     */
    public function index()
    {
        // Fetch available books via BookApiController::getAvailableBooks() (cross-module: Borrowing → Book)
        $apiUrl = config('app.api_url', config('app.url'));
        $availableBooks = [];
        $students = [];
        
        try {
            $response = Http::get("{$apiUrl}/api/books/available");
            
            if ($response->successful()) {
                $body = $response->body();
                // Remove BOM if present
                if (substr($body, 0, 3) === "\xEF\xBB\xBF") {
                    $body = substr($body, 3);
                }
                $jsonData = json_decode($body, true);
                
                if ($jsonData && isset($jsonData['success']) && $jsonData['success']) {
                    $availableBooks = $jsonData['data'] ?? [];
                } else {
                    \Log::error('API returned unsuccessful response', [
                        'status' => $response->status(),
                        'json' => $jsonData
                    ]);
                }
            } else {
                \Log::error('API call failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (Exception $e) {
            // Handle API error gracefully
            \Log::error('Exception calling books API: ' . $e->getMessage());
            $availableBooks = [];
        }

        // Fetch students for borrowing dropdown (cross-module: Borrowing → User)
        try {
            $response = Http::get("{$apiUrl}/api/users/role/Student");
            
            if ($response->successful()) {
                $body = $response->body();
                // Remove BOM if present
                if (substr($body, 0, 3) === "\xEF\xBB\xBF") {
                    $body = substr($body, 3);
                }
                $jsonData = json_decode($body, true);
                
                if ($jsonData && isset($jsonData['success']) && $jsonData['success']) {
                    $students = $jsonData['data'] ?? [];
                } else {
                    \Log::error('API returned unsuccessful response for students', [
                        'status' => $response->status(),
                        'json' => $jsonData
                    ]);
                }
            } else {
                \Log::error('API call failed for students', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (Exception $e) {
            // Handle API error gracefully
            \Log::error('Exception calling users API: ' . $e->getMessage());
            $students = [];
        }
        
        return view('borrowings.index', [
            'availableBooks' => $availableBooks,
            'students' => $students
        ]);
    }

    /**
     * 1. ISSUE BOOK TO STUDENTS - Using State Pattern (via Service)
     */
    public function borrow(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        $request->validate([
            'book_id' => 'required|exists:books,bookId',
            'user_id' => $isStaff ? 'required|exists:users,id' : 'nullable',
            'duration_days' => 'nullable|integer|min:1|max:30',
        ], [
            'user_id.required' => 'Please enter the User ID of the student borrowing the book.',
        ]);

        $userId = $isStaff ? $request->user_id : auth()->id();
        $durationDays = $request->duration_days ?? null;

        try {
            // Delegate to service
            $borrowing = $this->borrowingService->borrowBook($userId, $request->book_id, $durationDays);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book issued successfully',
                    'data' => $borrowing,
                    'state_pattern' => 'Transition: Available -> Borrowed'
                ]);
            }

            return redirect()->back()->with('success', 'Book issued successfully! Due date: ' . $borrowing->due_date->format('M d, Y'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 2. RETURN BOOK - Using State Pattern (via Service)
     */
    public function return(Request $request, $borrowingId)
    {
        try {
            // Delegate to service
            $borrowing = $this->borrowingService->returnBook($borrowingId);

            $message = 'Book returned successfully!';
            if ($borrowing->fine_amount > 0) {
                $message .= ' Fine amount: $' . number_format($borrowing->fine_amount, 2);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $borrowing,
                    'state_pattern' => 'Transition: Borrowed/Overdue -> Returned'
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 3. EXTEND / DUE DATE MANAGEMENT - Using State Pattern (via Service)
     */
    public function renew($borrowingId, Request $request)
    {
        $request->validate([
            'additional_days' => 'nullable|integer|min:1|max:14',
        ]);

        $additionalDays = $request->additional_days ?? 7;

        try {
            // Delegate to service
            $borrowing = $this->borrowingService->renewBorrowing($borrowingId, $additionalDays);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Borrowing renewed successfully',
                    'data' => $borrowing,
                    'state_pattern' => 'State: Borrowed (extended due date)'
                ]);
            }

            return redirect()->back()->with('success', 'Borrowing renewed! New due date: ' . $borrowing->due_date->format('M d, Y'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 4. VIEW BORROW HISTORY - Using State Pattern (via Service)
     */
    public function myHistory(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
        $userId = auth()->id();

        // Get borrowings with state information from service
        $borrowings = $this->borrowingService->getBorrowingHistoryWithStates($userId, $isStaff);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $borrowings,
                'state_pattern' => 'Each record includes current state information'
            ]);
        }

        return view('borrowings.history', compact('borrowings'));
    }

    /**
     * 5. FINE / PENALTY PAYMENT MANAGEMENT - Using State Pattern (via Service)
     */
    public function myFines(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
        $userId = auth()->id();

        // Get fines with state info from service
        $fines = $this->borrowingService->getFinesWithStates($userId, $isStaff);

        // Calculate totals
        $totalUnpaid = $fines->where('status', 'unpaid')->sum('amount');
        $totalPaid = $fines->where('status', 'paid')->sum('amount');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'fines' => $fines,
                    'total_unpaid' => $totalUnpaid,
                    'total_paid' => $totalPaid,
                ],
                'state_pattern' => 'Fines originate from Overdue state during return'
            ]);
        }

        return view('borrowings.fines', [
            'fines' => $fines,
            'totalUnpaid' => $totalUnpaid,
            'totalPaid' => $totalPaid,
        ]);
    }

    /**
     * 5b. PAY FINE - Using State Pattern (via Service)
     */
    public function payFine(Request $request, $fineId)
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,online',
        ]);

        try {
            // Delegate to service
            $fine = $this->borrowingService->payFine($fineId, $request->payment_method ?? 'cash');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fine paid successfully',
                    'data' => $fine,
                    'state_pattern' => 'Fine cleared - user can now borrow again'
                ]);
            }

            return redirect()->back()->with('success', 'Fine of $' . number_format($fine->amount, 2) . ' paid successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reserve a book - Using Observer Pattern (via Service)
     */
    public function reserve(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        $request->validate([
            'book_id' => 'required|exists:books,bookId',
            'user_id' => $isStaff ? 'required|exists:users,id' : 'nullable',
            'expiry_days' => 'nullable|integer|min:1|max:7',
        ], [
            'user_id.required' => 'Please enter the User ID of the student reserving the book.',
        ]);

        $userId = $isStaff ? $request->user_id : auth()->id();
        $expiryDays = $request->expiry_days ?? null;

        try {
            // Delegate to service
            $reservation = $this->borrowingService->reserveBook($userId, $request->book_id, $expiryDays);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Book reserved successfully',
                    'data' => $reservation,
                ]);
            }

            return redirect()->back()->with('success', 'Book reserved successfully! Expires: ' . $reservation->expiry_date->format('M d, Y'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel reservation - Using State Pattern (via Service)
     */
    public function cancelReservation(Request $request, $reservationId)
    {
        try {
            // Delegate to service
            $reservation = $this->borrowingService->cancelReservation($reservationId);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reservation cancelled successfully',
                    'data' => $reservation,
                ]);
            }

            return redirect()->back()->with('success', 'Reservation cancelled successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Check book availability with state info - Using State Pattern (via Service)
     */
    public function checkAvailability($bookId)
    {
        try {
            // Delegate to service
            $availabilityData = $this->borrowingService->getBookAvailabilityWithState($bookId);

            return response()->json([
                'success' => true,
                'data' => $availabilityData,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Get all overdue borrowings - Using State Pattern (via Service)
     */
    public function overdueList()
    {
        // Delegate to service
        $overdue = $this->borrowingService->getOverdueBorrowingsWithStates();

        return response()->json([
            'success' => true,
            'data' => $overdue,
            'state_pattern' => 'All items in Overdue state'
        ]);
    }
}
