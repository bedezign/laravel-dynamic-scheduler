<?php

namespace BEDeZign\DynamicScheduler;

use BEDeZign\DynamicScheduler\Execptions\InvalidArgumentException;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\ManagesFrequencies;

/**
 * Class TextToSchedule
 * @package BEDeZign\DynamicScheduler
 *
 * Use plain text directives to update a scheduled event instance.
 * In combination with the `ScheduleProxy` this allows you use alternative ways to schedule tasks, like via chat.
 */
class TextToSchedule
{
    use ManagesFrequencies;

    private        $event;
    static private $methods;

    /**
     * TextToSchedule constructor.
     * @param mixed $event either an actual Event or the ScheduleProxy, primed with one.
     */
    public function __construct($event)
    {
        $this->event = $event;
    }

    /**
     * Applies the given text to the event.
     * Statements should be separated by a ',' or ';"
     */
    public function apply($text)
    {
        $sections = preg_split('/[,;]/', $text);
        foreach ($sections as $section) {
            // Split everything in words, keep quotes in mind
            preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $section, $parts);
            $parts = array_get($parts, 0, []);

            if ($this->parse($parts)) {
                continue;
            }

            // Special cases that require extra attention
            // First: see if we have numeric arguments and if writing those as text solves anything:
            $alternateParts = $this->replaceNumerics($parts);
            if ($this->parse($alternateParts)) {
                continue;
            }

            return false;
        }
        return true;
    }

    private function parse($parts)
    {
        $methods    = $this->getMethods();
        $mergedName = strtolower(implode('', $parts));

        foreach ($methods as $name => $method) {
            $identical = $name === $mergedName;
            /** @var \ReflectionMethod $method */
            $name = $method->getName();
            if ($identical || strpos($mergedName, $name) === 0) {
                $requiredParameterCount = $method->getNumberOfRequiredParameters();

                if ($identical) {
                    if ($requiredParameterCount === 0) {
                        // No parameters, just call
                        ($this->event)->$name();
                        return true;
                    } else {
                        throw new InvalidArgumentException("$name requires parameters, none given");
                    }
                }

                // @TODO add support for parameters
            }
        }
        return false;
    }

    private function replaceNumerics($parts)
    {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);

        $replacements = [];
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $part = $formatter->format($part);
            }
            $replacements[] = $part;
        }

        return $replacements;
    }

    private function getMethods()
    {
        if (!self::$methods) {
            $methods = [];
            $me      = new \ReflectionClass($this);
            foreach ($me->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methods[strtolower($method->getName())] = $method;
            }

            // Now sort based on the length of the name
            uksort($methods, function($a, $b) {
                $lA = strlen($a);
                $lB = strlen($b);
                return $lA > $lB ? - 1 : ($lA == $lB ? 0 : 1);
            });
            static::$methods = $methods;
        }

        return self::$methods;
    }
}
