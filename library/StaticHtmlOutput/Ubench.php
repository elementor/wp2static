<?php
/**
 * Ubench
 *
 * @package WP2Static
 */

// https://github.com/devster/ubench
class Ubench
{
    protected $start_time;

    protected $end_time;

    protected $memory_usage;

    /**
     * Sets start microtime
     *
     * @return void
     */
    public function start()
    {
        $this->start_time = microtime(true);
    }

    /**
     * Sets end microtime
     *
     * @return void
     * @throws Exception
     */
    public function end()
    {
        if (!$this->hasStarted())
        {
            throw new LogicException("You must call start()");
        }

        $this->end_time = microtime(true);
        $this->memory_usage = memory_get_usage(true);
    }

    /**
     * Returns the elapsed time, readable or not
     *
     * @param bool $raw
     * @param  string $format The format to display (printf format)
     * @return float|string
     * @throws Exception
     */
    public function getTime($raw = false, $format = null)
    {
        if (!$this->hasStarted())
        {
            throw new LogicException("You must call start()");
        }

        if (!$this->hasEnded())
        {
            throw new LogicException("You must call end()");
        }

        $elapsed = $this->end_time - $this->start_time;

        return $raw ? $elapsed : self::readableElapsedTime($elapsed, $format);
    }

    /**
     * Returns the memory usage at the end checkpoint
     *
     * @param  boolean $readable Whether the result must be human readable
     * @param  string  $format   The format to display (printf format)
     * @return string|float
     */
    public function getMemoryUsage($raw = false, $format = null)
    {
        return $raw ? $this->memory_usage : self::readableSize($this->memory_usage, $format);
    }

    /**
     * Returns the memory peak, readable or not
     *
     * @param  boolean $readable Whether the result must be human readable
     * @param  string  $format   The format to display (printf format)
     * @return string|float
     */
    public function getMemoryPeak($raw = false, $format = null)
    {
        $memory = memory_get_peak_usage(true);

        return $raw ? $memory : self::readableSize($memory, $format);
    }

    /**
     * Wraps a callable with start() and end() calls
     *
     * Additional arguments passed to this method will be passed to
     * the callable.
     *
     * @param callable $callable
     * @return mixed
     */
    public function run(callable $callable)
    {
        $arguments = func_get_args();
        array_shift($arguments);

        $this->start();
        $result = call_user_func_array($callable, $arguments);
        $this->end();

        return $result;
    }

    /**
     * Returns a human readable memory size
     *
     * @param   int    $size
     * @param   string $format   The format to display (printf format)
     * @param   int    $round
     * @return  string
     */
    public static function readableSize($size, $format = null, $round = 3)
    {
        $mod = 1024;

        if (is_null($format)) {
            $format = '%.2f%s';
        }

        $units = explode(' ','B Kb Mb Gb Tb');

        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        if (0 === $i) {
            $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
        }

        return sprintf($format, round($size, $round), $units[$i]);
    }

    /**
     * Returns a human readable elapsed time
     *
     * @param  float $microtime
     * @param  string  $format   The format to display (printf format)
     * @return string
     */
    public static function readableElapsedTime($microtime, $format = null, $round = 3)
    {
        if (is_null($format)) {
            $format = '%.3f%s';
        }

        if ($microtime >= 1) {
            $unit = 's';
            $time = round($microtime, $round);
        } else {
            $unit = 'ms';
            $time = round($microtime*1000);

            $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
        }

        return sprintf($format, $time, $unit);
    }

    public function hasEnded()
    {
        return isset($this->end_time);
    }

    public function hasStarted()
    {
        return isset($this->start_time);
    }
}

