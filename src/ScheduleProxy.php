<?php

namespace BEDeZign\DynamicScheduler;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Opis\Closure\SerializableClosure;


/**
 * Class ScheduleProxy
 * @package BEDeZign\DynamicScheduler
 *
 * Capture all calls to the Schedule and generated events so it can be converted into
 * a dynamically scheduled task. Since 2 out of 3 possible schedulable
 */
class ScheduleProxy
{
    /**
     * @var Schedule
     */
    private $schedule;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var ScheduledTask
     */
    private $task;

    private $setup = [];

    public function __construct()
    {
        $this->restart();
    }

    public static function proxy(Schedule $schedule)
    {
        $proxy           = new self;
        $proxy->schedule = $schedule;
        return $proxy;
    }

    public function restart()
    {
        $this->event = null;
        $this->setup = [];
        $this->task  = new ScheduledTask();

        return $this;
    }

    public function __call($name, $parameters)
    {
        $this->setup[] = [
            'schedule'   => null === $this->event,
            'call'       => $name,
            'parameters' => $this->evaluateParameters($parameters)
        ];

        if (!$this->event) {
            $result = ($this->schedule)->$name(...$parameters);

            if ($result instanceof Event) {
                $this->event      = $result;
                $this->task->type = $name;
                // Fetch default description and pass on
                $this->description($result->description);
            }
        } else {
            $result = ($this->event)->$name(...$parameters);
            if (in_array($name, ['name', 'description'])) {
                $this->task->description = head($parameters);
            }
        }

        $this->task->setup = $this->setup;

        return $result instanceof Schedule || $result instanceof Event ? $this : $result;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return ScheduledTask
     */
    public function getTask()
    {
        return $this->task;
    }

    private function evaluateParameters($parameters)
    {
        foreach ($parameters as $index => $value) {
            if (is_callable($value)) {
                // Assume anything callable is a closure
                $value = new SerializableClosure($value);
            }
            $parameters[$index] = serialize($value);
        }
        return $parameters;
    }
}
