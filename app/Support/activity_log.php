<?php

use App\Models\ActivityLog;

if (! function_exists('log_activity')) {
    function log_activity(
        ?int $teamId,
        ?int $userId,
        string $action,
        ?string $message = null,
        ?array $meta = null
    ): ActivityLog {
        return ActivityLog::create([
            'team_id' => $teamId,
            'user_id' => $userId,
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}