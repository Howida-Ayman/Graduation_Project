<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        if($user->role_id!=2&&$user->role_id!=3)
            {
                return response()->json([
                  'message'=>'Unauthorized. Doctors And TA only.'
               ],403);
            }
        return $next($request);
    }
}
