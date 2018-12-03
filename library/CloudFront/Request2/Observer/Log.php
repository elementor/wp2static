<?php
/**
 * HTTP_Request2_Observer_Log
 *
 * @package WP2Static
 */

require_once 'HTTP/Request2/Exception.php';
class HTTP_Request2_Observer_Log implements SplObserver
{
    protected $target = null;
    public $events = array(
        'connect',
        'sentHeaders',
        'sentBody',
        'receivedHeaders',
        'receivedBody',
        'disconnect',
    );

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
    
    protected function log($message)
    {
        if ($this->target instanceof Log) {
            $this->target->debug($message);
        } elseif (is_resource($this->target)) {
            fwrite($this->target, $message . "\r\n");
        }
    }
}

?>
