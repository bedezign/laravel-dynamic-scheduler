<?php

namespace BEDeZign\DynamicScheduler;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Application as ApplicationContract;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        if (array_get(request()->server(), 'argv.1') === 'schedule:run') {
            // Only run if the command schedule:run is active.
            $this->app->extend(Schedule::class, function(Schedule $schedule) {
                try {
                    $tasks = ScheduledTask::all();

                    foreach ($tasks as $task) {
                        $task->setup($schedule);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // Don't interfere with regular workings
                    \Log::error('Unable to retrieve the scheduled tasks. Did you run the migrations?');
                }

                return $schedule;
            });
        }
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'migrations');
    }
}
