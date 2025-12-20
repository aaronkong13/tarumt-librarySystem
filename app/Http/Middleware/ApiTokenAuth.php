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
     * Priority order:
     * 1. Check session authentication first (for logged-in users from web)
     * 2. Check Bearer token (for external API access)
     * 3. Reject if neither is available
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority 1: Check session authentication first (logged-in users)
        // This works for requests from authenticated web pages (like profile)
        if (auth()->check()) {
            return $next($request);
        }

        // Priority 2: Check Bearer token in Authorization header (external modules/apps)
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

        // Priority 3: No authentication found - reject request
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized: Please login or provide API token',
            'debug' => [
                'session_exists' => $request->hasSession(),
                'auth_check' => auth()->check(),
                'has_bearer_token' => $request->bearerToken() !== null,
            ]
        ], 401);
    }
}
