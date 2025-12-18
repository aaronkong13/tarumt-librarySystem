<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBookPermission
{
    /**
     * Handle an incoming request.
     * 
     * Restricts book modification (create, update, delete) to Staff and Admin only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: User not authenticated',
            ], 401);
        }

        // Staff and Admin can modify books, Students cannot
        if (!in_array($user->role, ['Staff', 'Admin'])) {
            return response()->json([
                'success' => false,
                'message' => "Forbidden: Only Staff and Admin can perform this action. Your role: {$user->role}",
            ], 403);
        }

        return $next($request);
    }
}
