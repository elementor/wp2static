<?php
namespace Aws\Api;
class DocModel
{
    private $docs;
    public function __construct(array $docs)
    {
        if (!extension_loaded('tidy')) {
            throw new \RuntimeException('The "tidy" PHP extension is required.');
        }
        $this->docs = $docs;
    }
    public function toArray()
    {
        return $this->docs;
    }
    public function getServiceDocs()
    {
        return isset($this->docs['service']) ? $this->docs['service'] : null;
    }
    public function getOperationDocs($operation)
    {
        return isset($this->docs['operations'][$operation])
            ? $this->docs['operations'][$operation]
            : null;
    }
    public function getErrorDocs($error)
    {
        return isset($this->docs['shapes'][$error]['base'])
            ? $this->docs['shapes'][$error]['base']
            : null;
    }
    public function getShapeDocs($shapeName, $parentName, $ref)
    {
        if (!isset($this->docs['shapes'][$shapeName])) {
            return '';
        }
        $result = '';
        $d = $this->docs['shapes'][$shapeName];
        if (isset($d['refs']["{$parentName}\$${ref}"])) {
            $result = $d['refs']["{$parentName}\$${ref}"];
        } elseif (isset($d['base'])) {
            $result = $d['base'];
        }
        if (isset($d['append'])) {
            $result .= $d['append'];
        }
        return $this->clean($result);
    }
    private function clean($content)
    {
        if (!$content) {
            return '';
        }
        $tidy = new \Tidy();
        $tidy->parseString($content, [
            'indent' => true,
            'doctype' => 'omit',
            'output-html' => true,
            'show-body-only' => true,
            'drop-empty-paras' => true,
            'drop-font-tags' => true,
            'drop-proprietary-attributes' => true,
            'hide-comments' => true,
            'logical-emphasis' => true
        ]);
        $tidy->cleanRepair();
        return (string) $content;
    }
}
