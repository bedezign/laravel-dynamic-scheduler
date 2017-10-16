<?php

namespace BEDeZign\DynamicScheduler;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ScheduledTask
 * @package BEDeZign\DynamicScheduler
 *
 * @property string $type
 */
class ScheduledTask extends Model
{
    protected $casts = ['setup' => 'json'];

    protected static function boot()
    {
        parent::boot();

        parent::creating(function (self $task) {
            if (!$task->user_id && $userId = \Auth::id()) {
                $task->user_id = $userId;
            }
        });
    }

    public function setup(Schedule $schedule)
    {
        $steps = $this->setup;
        $event = null;
        foreach ($steps as $step) {
            $forSchedule = array_get($step, 'schedule', false);
            $method = array_get($step, 'call');
            $parameters = array_map('unserialize', array_get($step, 'parameters', []));

            $result = ($forSchedule ? $schedule : $event)->$method(...$parameters);
            if ($result instanceof Event)
                $event = $result;
        }
    }
}
