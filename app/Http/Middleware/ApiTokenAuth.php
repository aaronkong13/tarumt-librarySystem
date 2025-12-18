<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     * 
     * Validates API token from Bearer header for external module access.
     * If no token provided, checks session authentication (frontend access).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check Bearer token in Authorization header (external modules)
        $token = $request->bearerToken();

        if ($token) {
            // Validate API token
            $user = User::where('api_token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Invalid API token',
                ], 401);
            }

            // Authenticate user via token
            auth()->setUser($user);

            return $next($request);
        }

        // Fall back to session authentication (frontend from :8000)
        if (auth()->check()) {
            return $next($request);
        }

        // No authentication found
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: Please login or provide API token',
        ], 401);
    }
}
