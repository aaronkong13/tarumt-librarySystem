<?php

namespace App\Http\Controllers;

use App\Services\FineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * FineController - Web Controller for Fine Management Module
 *
 * Architecture matches BorrowingController:
 * - Uses FineService for all business logic
 * - Controller only handles HTTP concerns (request/response)
 * - State Pattern integration for borrowing state awareness
 */
class FineController extends Controller
{
    private FineService $fineService;

    public function __construct()
    {
        $this->fineService = new FineService();
    }

    /**
     * Display all fines (Staff/Admin) or user's fines (Students)
     */
    public function index(Request $request)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);

        // Auto-process overdue fines before displaying
        $this->fineService->processOverdueFines();

        if ($isStaff) {
            $status = $request->get('status');
            $fines = $this->fineService->getAllFinesWithStates($status);
            $statistics = $this->fineService->getFineStatistics();
        } else {
            $fines = $this->fineService->getUserFinesWithStates(auth()->id());
            $statistics = $this->fineService->getUserFineSummary(auth()->id());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'fines' => $fines,
                    'statistics' => $statistics,
                ],
                'state_pattern' => 'Fines originate from Overdue state during return',
            ]);
        }

        return view('fines.index', [
            'fines' => $fines,
            'statistics' => $statistics,
            'isStaff' => $isStaff,
        ]);
    }

    /**
     * Display user's own fines
     */
    public function myFines(Request $request)
    {
        // Auto-process overdue fines for this user
        $this->fineService->processOverdueFines();

        $userId = auth()->id();
        $fines = $this->fineService->getUserFinesWithStates($userId);
        $summary = $this->fineService->getUserFineSummary($userId);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'fines' => $fines,
                    'summary' => $summary,
                ],
            ]);
        }

        return view('fines.my-fines', [
            'fines' => $fines,
            'summary' => $summary,
        ]);
    }

    /**
     * Pay a single fine
     */
    public function pay(Request $request, $fineId)
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,online',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $fine = $this->fineService->payFine(
                $fineId,
                $request->payment_method ?? 'cash',
                $request->notes
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fine paid successfully',
                    'data' => $fine,
                    'state_pattern' => 'Fine cleared - user can now borrow again',
                ]);
            }

            return redirect()->back()->with('success', 'Fine of RM ' . number_format($fine['amount'], 2) . ' paid successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pay all unpaid fines for a user (Staff/Admin only)
     */
    public function payAll(Request $request, $userId = null)
    {
        $request->validate([
            'payment_method' => 'nullable|in:cash,card,online',
        ]);

        // Staff can pay for any user, students can only pay their own
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
        $targetUserId = $isStaff && $userId ? $userId : auth()->id();

        try {
            $result = $this->fineService->payAllUserFines(
                $targetUserId,
                $request->payment_method ?? 'cash'
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'All fines paid successfully',
                    'data' => $result,
                ]);
            }

            return redirect()->back()->with('success', 
                $result['fines_paid'] . ' fines totaling RM ' . number_format($result['total_amount'], 2) . ' paid successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Waive a fine (Staff/Admin only)
     */
    public function waive(Request $request, $fineId)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $fine = $this->fineService->waiveFine($fineId, $request->reason);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fine waived successfully',
                    'data' => $fine,
                ]);
            }

            return redirect()->back()->with('success', 'Fine of RM ' . number_format($fine['amount'], 2) . ' waived successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get fine details
     */
    public function show($fineId)
    {
        try {
            $fine = $this->fineService->getFineById($fineId);

            return response()->json([
                'success' => true,
                'data' => $fine,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Get fine statistics (Staff/Admin only)
     */
    public function statistics()
    {
        $statistics = $this->fineService->getFineStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Process all overdue fines (Staff/Admin only)
     * This manually triggers the fine processing
     */
    public function processOverdue(Request $request)
    {
        try {
            $result = $this->fineService->processOverdueFines();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Overdue fines processed successfully',
                    'data' => $result,
                ]);
            }

            return redirect()->back()->with('success', 
                count($result) . ' overdue borrowings processed for fines.');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generate fine report (Staff/Admin only)
     */
    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $report = $this->fineService->generateFineReport(
            $request->start_date,
            $request->end_date
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        }

        return view('fines.report', ['report' => $report]);
    }

    /**
     * Get user's fine summary
     */
    public function userSummary($userId = null)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
        $targetUserId = $isStaff && $userId ? $userId : auth()->id();

        $summary = $this->fineService->getUserFineSummary($targetUserId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Check if user has unpaid fines
     */
    public function checkUnpaid($userId = null)
    {
        $isStaff = in_array(auth()->user()->role, ['Staff', 'Admin']);
        $targetUserId = $isStaff && $userId ? $userId : auth()->id();

        $hasUnpaid = $this->fineService->userHasUnpaidFines($targetUserId);
        $totalUnpaid = $this->fineService->getUserTotalUnpaidFines($targetUserId);

        return response()->json([
            'success' => true,
            'data' => [
                'has_unpaid_fines' => $hasUnpaid,
                'total_unpaid_amount' => $totalUnpaid,
            ],
        ]);
    }
}
