<?php


namespace Golly\DirtyLog;

use Illuminate\Support\ServiceProvider;

/**
 * Class DirtyLogServiceProvider
 * @package Golly\DirtyLog
 */
class DirtyLogServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../migrations/create_dirty_logs_table.php.stub' => database_path("/migrations/2021_02_01_131655_create_dirty_logs_table.php"),
            ], 'migrations');
        }
    }
}
