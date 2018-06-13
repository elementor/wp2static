<?php
/**
 * An observer useful for debugging / testing.
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    David Jean Louis <izi@php.net>
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2016 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

/**
 * Exception class for HTTP_Request2 package
 */
require_once 'HTTP/Request2/Exception.php';

/**
 * A debug observer useful for debugging / testing.
 *
 * This observer logs to a log target data corresponding to the various request
 * and response events, it logs by default to php://output but can be configured
 * to log to a file or via the PEAR Log package.
 *
 * A simple example:
 * <code>
 * require_once 'HTTP/Request2.php';
 * require_once 'HTTP/Request2/Observer/Log.php';
 *
 * $request  = new HTTP_Request2('http://www.example.com');
 * $observer = new HTTP_Request2_Observer_Log();
 * $request->attach($observer);
 * $request->send();
 * </code>
 *
 * A more complex example with PEAR Log:
 * <code>
 * require_once 'HTTP/Request2.php';
 * require_once 'HTTP/Request2/Observer/Log.php';
 * require_once 'Log.php';
 *
 * $request  = new HTTP_Request2('http://www.example.com');
 * // we want to log with PEAR log
 * $observer = new HTTP_Request2_Observer_Log(Log::factory('console'));
 *
 * // we only want to log received headers
 * $observer->events = array('receivedHeaders');
 *
 * $request->attach($observer);
 * $request->send();
 * </code>
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   David Jean Louis <izi@php.net>
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_Observer_Log implements SplObserver
{
    // properties {{{

    /**
     * The log target, it can be a a resource or a PEAR Log instance.
     *
     * @var resource|Log $target
     */
    protected $target = null;

    /**
     * The events to log.
     *
     * @var array $events
     */
    public $events = array(
        'connect',
        'sentHeaders',
        'sentBody',
        'receivedHeaders',
        'receivedBody',
        'disconnect',
    );

    // }}}
    // __construct() {{{

    /**
     * Constructor.
     *
     * @param mixed $target Can be a file path (default: php://output), a resource,
     *                      or an instance of the PEAR Log class.
     * @param array $events Array of events to listen to (default: all events)
     *
     * @return void
     */
    public function __construct($target = 'php://output', array $events = array())
    {
        if (!empty($events)) {
            $this->events = $events;
        }
        if (is_resource($target) || $target instanceof Log) {
            $this->target = $target;
        } elseif (false === ($this->target = @fopen($target, 'ab'))) {
            throw new HTTP_Request2_Exception("Unable to open '{$target}'");
        }
    }

    // }}}
    // update() {{{

    /**
     * Called when the request notifies us of an event.
     *
     * @param HTTP_Request2 $subject The HTTP_Request2 instance
     *
     * @return void
     */
    public function update(SplSubject $subject)
    {
        $event = $subject->getLastEvent();
        if (!in_array($event['name'], $this->events)) {
            return;
        }

        switch ($event['name']) {
        case 'connect':
            $this->log('* Connected to ' . $event['data']);
            break;
        case 'sentHeaders':
            $headers = explode("\r\n", $event['data']);
            array_pop($headers);
            foreach ($headers as $header) {
                $this->log('> ' . $header);
            }
            break;
        case 'sentBody':
            $this->log('> ' . $event['data'] . ' byte(s) sent');
            break;
        case 'receivedHeaders':
            $this->log(sprintf(
                '< HTTP/%s %s %s', $event['data']->getVersion(),
                $event['data']->getStatus(), $event['data']->getReasonPhrase()
            ));
            $headers = $event['data']->getHeader();
            foreach ($headers as $key => $val) {
                $this->log('< ' . $key . ': ' . $val);
            }
            $this->log('< ');
            break;
        case 'receivedBody':
            $this->log($event['data']->getBody());
            break;
        case 'disconnect':
            $this->log('* Disconnected');
            break;
        }
    }

    // }}}
    // log() {{{

    /**
     * Logs the given message to the configured target.
     *
     * @param string $message Message to display
     *
     * @return void
     */
    protected function log($message)
    {
        if ($this->target instanceof Log) {
            $this->target->debug($message);
        } elseif (is_resource($this->target)) {
            fwrite($this->target, $message . "\r\n");
        }
    }

    // }}}
}

?>