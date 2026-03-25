<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CatchErrorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}