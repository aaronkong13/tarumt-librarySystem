<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BlockApiRoutesOnFrontend Middleware
 * 
 * Prevents access to /api/* routes on the frontend server (port 8000).
 * API routes should ONLY be accessible from the backend server (port 8001).
 * 
 * Architecture:
 * - Frontend (8000): /books, /users, /borrowings (views/frontend routes)
 * - Backend (8001):  /api/books, /api/users, /api/borrowings (API endpoints)
 * - BLOCKED: 8000/api/* (this middleware prevents this)
 */
class BlockApiRoutesOnFrontend
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the current server port
        $currentPort = $request->getPort();
        
        // ONLY block /api/* routes on port 8000 (frontend)
        // Port 8001 (backend) should allow /api/* routes
        if ($currentPort == 8000 && $request->is('api/*')) {
            return response()->json([
                'error' => 'API routes are not accessible on the frontend server.',
                'message' => 'Please access API endpoints via the backend server at http://localhost:8001',
                'frontend_url' => 'http://localhost:8000 - For web pages and views',
                'backend_url' => 'http://localhost:8001 - For API endpoints',
            ], 403);
        }

        return $next($request);
    }
}
