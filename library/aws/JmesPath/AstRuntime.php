<?php
namespace JmesPath;
class AstRuntime
{
    private $parser;
    private $interpreter;
    private $cache = [];
    private $cachedCount = 0;
    public function __construct(
        Parser $parser = null,
        callable $fnDispatcher = null
    ) {
        $fnDispatcher = $fnDispatcher ?: FnDispatcher::getInstance();
        $this->interpreter = new TreeInterpreter($fnDispatcher);
        $this->parser = $parser ?: new Parser();
    }
    public function __invoke($expression, $data)
    {
        if (!isset($this->cache[$expression])) {
            if (++$this->cachedCount > 1024) {
                $this->cache = [];
                $this->cachedCount = 0;
            }
            $this->cache[$expression] = $this->parser->parse($expression);
        }
        return $this->interpreter->visit($this->cache[$expression], $data);
    }
}
