<?php
namespace Monolog\Handler;
use Monolog\Formatter\LineFormatter;
class BrowserConsoleHandler extends AbstractProcessingHandler
{
    protected static $initialized = false;
    protected static $records = array();
    protected function getDefaultFormatter()
    {
        return new LineFormatter('[[%channel%]]{macro: autolabel} [[%level_name%]]{font-weight: bold} %message%');
    }
    protected function write(array $record)
    {
        self::$records[] = $record;
        if (!self::$initialized) {
            self::$initialized = true;
            $this->registerShutdownFunction();
        }
    }
    public static function send()
    {
        $format = self::getResponseFormat();
        if ($format === 'unknown') {
            return;
        }
        if (count(self::$records)) {
            if ($format === 'html') {
                self::writeOutput('<script>' . self::generateScript() . '</script>');
            } elseif ($format === 'js') {
                self::writeOutput(self::generateScript());
            }
            self::reset();
        }
    }
    public static function reset()
    {
        self::$records = array();
    }
    protected function registerShutdownFunction()
    {
        if (PHP_SAPI !== 'cli') {
            register_shutdown_function(array('Monolog\Handler\BrowserConsoleHandler', 'send'));
        }
    }
    protected static function writeOutput($str)
    {
        echo $str;
    }
    protected static function getResponseFormat()
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'content-type:') === 0) {
                if (stripos($header, 'application/javascript') !== false || stripos($header, 'text/javascript') !== false) {
                    return 'js';
                }
                if (stripos($header, 'text/html') === false) {
                    return 'unknown';
                }
                break;
            }
        }
        return 'html';
    }
    private static function generateScript()
    {
        $script = array();
        foreach (self::$records as $record) {
            $context = self::dump('Context', $record['context']);
            $extra = self::dump('Extra', $record['extra']);
            if (empty($context) && empty($extra)) {
                $script[] = self::call_array('log', self::handleStyles($record['formatted']));
            } else {
                $script = array_merge($script,
                    array(self::call_array('groupCollapsed', self::handleStyles($record['formatted']))),
                    $context,
                    $extra,
                    array(self::call('groupEnd'))
                );
            }
        }
        return "(function (c) {if (c && c.groupCollapsed) {\n" . implode("\n", $script) . "\n}})(console);";
    }
    private static function handleStyles($formatted)
    {
        $args = array(self::quote('font-weight: normal'));
        $format = '%c' . $formatted;
        preg_match_all('/\[\[(.*?)\]\]\{([^}]*)\}/s', $format, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach (array_reverse($matches) as $match) {
            $args[] = self::quote(self::handleCustomStyles($match[2][0], $match[1][0]));
            $args[] = '"font-weight: normal"';
            $pos = $match[0][1];
            $format = substr($format, 0, $pos) . '%c' . $match[1][0] . '%c' . substr($format, $pos + strlen($match[0][0]));
        }
        array_unshift($args, self::quote($format));
        return $args;
    }
    private static function handleCustomStyles($style, $string)
    {
        static $colors = array('blue', 'green', 'red', 'magenta', 'orange', 'black', 'grey');
        static $labels = array();
        return preg_replace_callback('/macro\s*:(.*?)(?:;|$)/', function ($m) use ($string, &$colors, &$labels) {
            if (trim($m[1]) === 'autolabel') {
                if (!isset($labels[$string])) {
                    $labels[$string] = $colors[count($labels) % count($colors)];
                }
                $color = $labels[$string];
                return "background-color: $color; color: white; border-radius: 3px; padding: 0 2px 0 2px";
            }
            return $m[1];
        }, $style);
    }
    private static function dump($title, array $dict)
    {
        $script = array();
        $dict = array_filter($dict);
        if (empty($dict)) {
            return $script;
        }
        $script[] = self::call('log', self::quote('%c%s'), self::quote('font-weight: bold'), self::quote($title));
        foreach ($dict as $key => $value) {
            $value = json_encode($value);
            if (empty($value)) {
                $value = self::quote('');
            }
            $script[] = self::call('log', self::quote('%s: %o'), self::quote($key), $value);
        }
        return $script;
    }
    private static function quote($arg)
    {
        return '"' . addcslashes($arg, "\"\n\\") . '"';
    }
    private static function call()
    {
        $args = func_get_args();
        $method = array_shift($args);
        return self::call_array($method, $args);
    }
    private static function call_array($method, array $args)
    {
        return 'c.' . $method . '(' . implode(', ', $args) . ');';
    }
}
