<?php
namespace Monolog\Handler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
class SlackHandler extends SocketHandler
{
    private $token;
    private $channel;
    private $username;
    private $iconEmoji;
    private $useAttachment;
    private $useShortAttachment;
    private $includeContextAndExtra;
    private $lineFormatter;
    public function __construct($token, $channel, $username = 'Monolog', $useAttachment = true, $iconEmoji = null, $level = Logger::CRITICAL, $bubble = true, $useShortAttachment = false, $includeContextAndExtra = false)
    {
        if (!extension_loaded('openssl')) {
            throw new MissingExtensionException('The OpenSSL PHP extension is required to use the SlackHandler');
        }
        parent::__construct('ssl:
        $this->token = $token;
        $this->channel = $channel;
        $this->username = $username;
        $this->iconEmoji = trim($iconEmoji, ':');
        $this->useAttachment = $useAttachment;
        $this->useShortAttachment = $useShortAttachment;
        $this->includeContextAndExtra = $includeContextAndExtra;
        if ($this->includeContextAndExtra && $this->useShortAttachment) {
            $this->lineFormatter = new LineFormatter;
        }
    }
    protected function generateDataStream($record)
    {
        $content = $this->buildContent($record);
        return $this->buildHeader($content) . $content;
    }
    private function buildContent($record)
    {
        $dataArray = $this->prepareContentData($record);
        return http_build_query($dataArray);
    }
    protected function prepareContentData($record)
    {
        $dataArray = array(
            'token'       => $this->token,
            'channel'     => $this->channel,
            'username'    => $this->username,
            'text'        => '',
            'attachments' => array(),
        );
        if ($this->useAttachment) {
            $attachment = array(
                'fallback' => $record['message'],
                'color'    => $this->getAttachmentColor($record['level']),
                'fields'   => array(),
            );
            if ($this->useShortAttachment) {
                $attachment['title'] = $record['level_name'];
                $attachment['text'] = $record['message'];
            } else {
                $attachment['title'] = 'Message';
                $attachment['text'] = $record['message'];
                $attachment['fields'][] = array(
                    'title' => 'Level',
                    'value' => $record['level_name'],
                    'short' => true,
                );
            }
            if ($this->includeContextAndExtra) {
                if (!empty($record['extra'])) {
                    if ($this->useShortAttachment) {
                        $attachment['fields'][] = array(
                            'title' => "Extra",
                            'value' => $this->stringify($record['extra']),
                            'short' => $this->useShortAttachment,
                        );
                    } else {
                        foreach ($record['extra'] as $var => $val) {
                            $attachment['fields'][] = array(
                                'title' => $var,
                                'value' => $val,
                                'short' => $this->useShortAttachment,
                            );
                        }
                    }
                }
                if (!empty($record['context'])) {
                    if ($this->useShortAttachment) {
                        $attachment['fields'][] = array(
                            'title' => "Context",
                            'value' => $this->stringify($record['context']),
                            'short' => $this->useShortAttachment,
                        );
                    } else {
                        foreach ($record['context'] as $var => $val) {
                            $attachment['fields'][] = array(
                                'title' => $var,
                                'value' => $val,
                                'short' => $this->useShortAttachment,
                            );
                        }
                    }
                }
            }
            $dataArray['attachments'] = json_encode(array($attachment));
        } else {
            $dataArray['text'] = $record['message'];
        }
        if ($this->iconEmoji) {
            $dataArray['icon_emoji'] = ":{$this->iconEmoji}:";
        }
        return $dataArray;
    }
    private function buildHeader($content)
    {
        $header = "POST /api/chat.postMessage HTTP/1.1\r\n";
        $header .= "Host: slack.com\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "\r\n";
        return $header;
    }
    protected function write(array $record)
    {
        parent::write($record);
        $res = $this->getResource();
        if (is_resource($res)) {
            @fread($res, 2048);
        }
        $this->closeSocket();
    }
    protected function getAttachmentColor($level)
    {
        switch (true) {
            case $level >= Logger::ERROR:
                return 'danger';
            case $level >= Logger::WARNING:
                return 'warning';
            case $level >= Logger::INFO:
                return 'good';
            default:
                return '#e3e4e6';
        }
    }
    protected function stringify($fields)
    {
        $string = '';
        foreach ($fields as $var => $val) {
            $string .= $var.': '.$this->lineFormatter->stringify($val)." | ";
        }
        $string = rtrim($string, " |");
        return $string;
    }
}
