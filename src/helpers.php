<?php

use Golly\DirtyLog\DirtyLogger;

if (!function_exists('dirty')) {
    function dirty(string $logName = null): DirtyLogger
    {
        return app(DirtyLogger::class)->useLog($logName);
    }
}
