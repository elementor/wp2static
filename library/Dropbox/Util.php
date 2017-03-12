<?php
namespace Dropbox;

class Util
{
    /**
     * @internal
     */
    public static function q($object)
    {
        return var_export($object, true);
    }

    /**
     * If the given string begins with the UTF-8 BOM (byte order mark), remove it and
     * return whatever is left.  Otherwise, return the original string untouched.
     *
     * Though it's not recommended for UTF-8 to have a BOM, the standard allows it to
     * support software that isn't Unicode-aware.
     *
     * @param string $string
     *    A UTF-8 encoded string.
     *
     * @return string
     */
    public static function stripUtf8Bom($string)
    {
        if (\substr_compare($string, "\xEF\xBB\xBF", 0, 3) === 0) {
            $string = \substr($string, 3);
        }
        return $string;
    }
}
