<?php
namespace JmesPath;
final class Env
{
    const COMPILE_DIR = 'JP_PHP_COMPILE';
    public static function search($expression, $data)
    {
        static $runtime;
        if (!$runtime) {
            $runtime = Env::createRuntime();
        }
        return $runtime($expression, $data);
    }
    public static function createRuntime()
    {
        switch ($compileDir = getenv(self::COMPILE_DIR)) {
            case false: return new AstRuntime();
            case 'on': return new CompilerRuntime();
            default: return new CompilerRuntime($compileDir);
        }
    }
    public static function cleanCompileDir()
    {
        $total = 0;
        $compileDir = getenv(self::COMPILE_DIR) ?: sys_get_temp_dir();
        foreach (glob("{$compileDir}/jmespath_*.php") as $file) {
            $total++;
            unlink($file);
        }
        return $total;
    }
}
