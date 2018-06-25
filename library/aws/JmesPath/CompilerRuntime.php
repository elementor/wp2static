<?php
namespace JmesPath;
class CompilerRuntime
{
    private $parser;
    private $compiler;
    private $cacheDir;
    private $interpreter;
    public function __construct($dir = null, Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
        $this->compiler = new TreeCompiler();
        $dir = $dir ?: sys_get_temp_dir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Unable to create cache directory: $dir");
        }
        $this->cacheDir = realpath($dir);
        $this->interpreter = new TreeInterpreter();
    }
    public function __invoke($expression, $data)
    {
        $functionName = 'jmespath_' . md5($expression);
        if (!function_exists($functionName)) {
            $filename = "{$this->cacheDir}/{$functionName}.php";
            if (!file_exists($filename)) {
                $this->compile($filename, $expression, $functionName);
            }
            require $filename;
        }
        return $functionName($this->interpreter, $data);
    }
    private function compile($filename, $expression, $functionName)
    {
        $code = $this->compiler->visit(
            $this->parser->parse($expression),
            $functionName,
            $expression
        );
        if (!file_put_contents($filename, $code)) {
            throw new \RuntimeException(sprintf(
                'Unable to write the compiled PHP code to: %s (%s)',
                $filename,
                var_export(error_get_last(), true)
            ));
        }
    }
}
