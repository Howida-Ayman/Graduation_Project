<?php

use App\Models\ActivityLog;
use App\Models\AcademicYear;

if (! function_exists('log_activity')) {
    function log_activity(
        ?int $teamId,
        ?int $userId,
        string $action,
        ?string $message = null,
        ?array $meta = null,
        ?int $academicYearId = null
    ): ActivityLog {
        return ActivityLog::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'academic_year_id' => $academicYearId ?? AcademicYear::where('is_active', true)->value('id'),
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}