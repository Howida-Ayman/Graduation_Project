<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TeamMembership;

class CheckTeamStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // التحقق من وجود الفريق
        $membership = TeamMembership::where('student_user_id', $user->id)
            ->where('status', 'active')
            ->first();
        
        // تخزين حالة الفريق في الـ request
        $request->merge([
            'has_team' => !is_null($membership),
            'team_id' => $membership?->team_id,
            'is_leader' => $membership && $membership->team->leader_user_id == $user->id,
        ]);
        
        return $next($request);
    }
}