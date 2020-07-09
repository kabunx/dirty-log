<?php

use Golly\ActivityLog\ActivityLogger;

if (!function_exists('activity')) {
    function activity(string $logName = null): ActivityLogger
    {
        return app(ActivityLogger::class)->useLog($logName);
    }
}
