<?php
namespace JmesPath;
if (!function_exists(__NAMESPACE__ . '\search')) {
    function search($expression, $data)
    {
        return Env::search($expression, $data);
    }
}
