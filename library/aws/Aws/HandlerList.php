<?php
namespace Aws;
class HandlerList implements \Countable
{
    const INIT = 'init';
    const VALIDATE = 'validate';
    const BUILD = 'build';
    const SIGN = 'sign';
    private $handler;
    private $named = [];
    private $sorted;
    private $interposeFn;
    private $steps = [
        self::SIGN     => [],
        self::BUILD    => [],
        self::VALIDATE => [],
        self::INIT     => [],
    ];
    public function __construct(callable $handler = null)
    {
        $this->handler = $handler;
    }
    public function __toString()
    {
        $str = '';
        $i = 0;
        foreach (array_reverse($this->steps) as $k => $step) {
            foreach (array_reverse($step) as $j => $tuple) {
                $str .= "{$i}) Step: {$k}, ";
                if ($tuple[1]) {
                    $str .= "Name: {$tuple[1]}, ";
                }
                $str .= "Function: " . $this->debugCallable($tuple[0]) . "\n";
                $i++;
            }
        }
        if ($this->handler) {
            $str .= "{$i}) Handler: " . $this->debugCallable($this->handler) . "\n";
        }
        return $str;
    }
    public function setHandler(callable $handler)
    {
        $this->handler = $handler;
    }
    public function hasHandler()
    {
        return (bool) $this->handler;
    }
    public function appendInit(callable $middleware, $name = null)
    {
        $this->add(self::INIT, $name, $middleware);
    }
    public function prependInit(callable $middleware, $name = null)
    {
        $this->add(self::INIT, $name, $middleware, true);
    }
    public function appendValidate(callable $middleware, $name = null)
    {
        $this->add(self::VALIDATE, $name, $middleware);
    }
    public function prependValidate(callable $middleware, $name = null)
    {
        $this->add(self::VALIDATE, $name, $middleware, true);
    }
    public function appendBuild(callable $middleware, $name = null)
    {
        $this->add(self::BUILD, $name, $middleware);
    }
    public function prependBuild(callable $middleware, $name = null)
    {
        $this->add(self::BUILD, $name, $middleware, true);
    }
    public function appendSign(callable $middleware, $name = null)
    {
        $this->add(self::SIGN, $name, $middleware);
    }
    public function prependSign(callable $middleware, $name = null)
    {
        $this->add(self::SIGN, $name, $middleware, true);
    }
    public function before($findName, $withName, callable $middleware)
    {
        $this->splice($findName, $withName, $middleware, true);
    }
    public function after($findName, $withName, callable $middleware)
    {
        $this->splice($findName, $withName, $middleware, false);
    }
    public function remove($nameOrInstance)
    {
        if (is_callable($nameOrInstance)) {
            $this->removeByInstance($nameOrInstance);
        } elseif (is_string($nameOrInstance)) {
            $this->removeByName($nameOrInstance);
        }
    }
    public function interpose(callable $fn = null)
    {
        $this->sorted = null;
        $this->interposeFn = $fn;
    }
    public function resolve()
    {
        if (!($prev = $this->handler)) {
            throw new \LogicException('No handler has been specified');
        }
        if ($this->sorted === null) {
            $this->sortMiddleware();
        }
        foreach ($this->sorted as $fn) {
            $prev = $fn($prev);
        }
        return $prev;
    }
    public function count()
    {
        return count($this->steps[self::INIT])
            + count($this->steps[self::VALIDATE])
            + count($this->steps[self::BUILD])
            + count($this->steps[self::SIGN]);
    }
    private function splice($findName, $withName, callable $middleware, $before)
    {
        if (!isset($this->named[$findName])) {
            throw new \InvalidArgumentException("$findName not found");
        }
        $idx = $this->sorted = null;
        $step = $this->named[$findName];
        if ($withName) {
            $this->named[$withName] = $step;
        }
        foreach ($this->steps[$step] as $i => $tuple) {
            if ($tuple[1] === $findName) {
                $idx = $i;
                break;
            }
        }
        $replacement = $before
            ? [$this->steps[$step][$idx], [$middleware, $withName]]
            : [[$middleware, $withName], $this->steps[$step][$idx]];
        array_splice($this->steps[$step], $idx, 1, $replacement);
    }
    private function debugCallable($fn)
    {
        if (is_string($fn)) {
            return "callable({$fn})";
        }
        if (is_array($fn)) {
            $ele = is_string($fn[0]) ? $fn[0] : get_class($fn[0]);
            return "callable(['{$ele}', '{$fn[1]}'])";
        }
        return 'callable(' . spl_object_hash($fn) . ')';
    }
    private function sortMiddleware()
    {
        $this->sorted = [];
        if (!$this->interposeFn) {
            foreach ($this->steps as $step) {
                foreach ($step as $fn) {
                    $this->sorted[] = $fn[0];
                }
            }
            return;
        }
        $ifn = $this->interposeFn;
        foreach ($this->steps as $stepName => $step) {
            foreach ($step as $fn) {
                $this->sorted[] = $ifn($stepName, $fn[1]);
                $this->sorted[] = $fn[0];
            }
        }
    }
    private function removeByName($name)
    {
        if (!isset($this->named[$name])) {
            return;
        }
        $this->sorted = null;
        $step = $this->named[$name];
        $this->steps[$step] = array_values(
            array_filter(
                $this->steps[$step],
                function ($tuple) use ($name) {
                    return $tuple[1] !== $name;
                }
            )
        );
    }
    private function removeByInstance(callable $fn)
    {
        foreach ($this->steps as $k => $step) {
            foreach ($step as $j => $tuple) {
                if ($tuple[0] === $fn) {
                    $this->sorted = null;
                    unset($this->named[$this->steps[$k][$j][1]]);
                    unset($this->steps[$k][$j]);
                }
            }
        }
    }
    private function add($step, $name, callable $middleware, $prepend = false)
    {
        $this->sorted = null;
        if ($prepend) {
            $this->steps[$step][] = [$middleware, $name];
        } else {
            array_unshift($this->steps[$step], [$middleware, $name]);
        }
        if ($name) {
            $this->named[$name] = $step;
        }
    }
}
