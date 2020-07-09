<?php


namespace Golly\DirtyLog;

use Illuminate\Support\ServiceProvider;

/**
 * Class DirtyLogServiceProvider
 * @package Golly\DirtyLog
 */
class DirtyLogServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if (!class_exists('CreateDirtyLogsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/../migrations/create_dirty_logs_table.php.stub' => database_path("/migrations/{$timestamp}_create_dirty_logs_table.php"),
            ], 'migrations');
        }
    }
}
