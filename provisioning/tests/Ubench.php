<?php
/**
 * Ubench
 *
 * @package WP2Static
 *
 * https://github.com/devster/ubench
 */
class Ubench {

    protected $start_time;

    protected $end_time;

    protected $memory_usage;

    /**
     * Sets start microtime
     *
     * @return void
     */
    public function start() {
        $this->start_time = microtime( true );
    }

    /**
     * Sets end microtime
     *
     * @return void
     * @throws LogicException If not started
     */
    public function end() {
        if ( ! $this->hasStarted() ) {
            throw new LogicException( 'You must call start()' );
        }

        $this->end_time = microtime( true );
        $this->memory_usage = memory_get_usage( true );
    }

    /**
     * Returns the elapsed time, readable or not
     *
     * @param boolean $raw    Raw output flag
     * @param string  $format Display format (printf)
     * @return float|string
     * @throws LogicException If not started/ended
     */
    public function getTime( $raw = false, $format = null ) {
        if ( ! $this->hasStarted() ) {
            throw new LogicException( 'You must call start()' );
        }

        if ( ! $this->hasEnded() ) {
            throw new LogicException( 'You must call end()' );
        }

        $elapsed = $this->end_time - $this->start_time;

        return $raw ? $elapsed : self::readableElapsedTime( $elapsed, $format );
    }

    /**
     * Returns the memory usage at the end checkpoint
     *
     * @param  boolean $raw    Raw output flag
     * @param  string  $format Display format (printf)
     * @return string|float
     */
    public function getMemoryUsage( $raw = false, $format = null ) {
        return $raw
            ? $this->memory_usage
            : self::readableSize( $this->memory_usage, $format );
    }

    /**
     * Returns the memory peak, readable or not
     *
     * @param  boolean $raw    Raw result flag
     * @param  string  $format Display format (printf)
     * @return string|float
     */
    public function getMemoryPeak( $raw = false, $format = null ) {
        $memory = memory_get_peak_usage( true );

        return $raw
            ? $memory
            : self::readableSize( $memory, $format );
    }

    /**
     * Wraps a callable with start() and end() calls
     *
     * Additional arguments passed to this method will be passed to
     * the callable.
     *
     * @param callable $callable Function to call
     * @return mixed
     */
    public function run( callable $callable ) {
        $arguments = func_get_args();
        array_shift( $arguments );

        $this->start();
        $result = call_user_func_array( $callable, $arguments );
        $this->end();

        return $result;
    }

    /**
     * Returns a human readable memory size
     *
     * @param   int    $size   Size
     * @param   string $format Display format (printf)
     * @param   int    $round  Rounding precision
     * @return  string
     */
    public static function readableSize( $size, $format = null, $round = 3 ) {
        $mod = 1024;

        if ( is_null( $format ) ) {
            $format = '%.2f%s';
        }

        $units = explode( ' ', 'B Kb Mb Gb Tb' );

        for ( $i = 0; $size > $mod; $i++ ) {
            $size /= $mod;
        }

        if ( 0 === $i ) {
            $format = preg_replace( '/(%.[\d]+f)/', '%d', $format );
        }

        return sprintf( $format, round( $size, $round ), $units[ $i ] );
    }

    /**
     * Returns a human readable elapsed time
     *
     * @param  float   $microtime Microtime
     * @param  string  $format    Display format (printf)
     * @param  integer $round     Rounding precision
     * @return string
     */
    public static function readableElapsedTime(
        $microtime,
        $format = null,
        $round = 3
    ) {
        if ( is_null( $format ) ) {
            $format = '%.3f%s';
        }

        if ( $microtime >= 1 ) {
            $unit = 's';
            $time = round( $microtime, $round );
        } else {
            $unit = 'ms';
            $time = round( $microtime * 1000 );

            $format = preg_replace( '/(%.[\d]+f)/', '%d', $format );
        }

        return sprintf( $format, $time, $unit );
    }


    /**
     * Check whether ended
     *
     * @return boolean
     */
    public function hasEnded() {
        return isset( $this->end_time );
    }


    /**
     * Check whether started
     *
     * @return boolean
     */
    public function hasStarted() {
        return isset( $this->start_time );
    }

}


