<?php
namespace Dropbox;

/**
 * User is over Dropbox storage quota.
 */
final class Exception_OverQuota extends Exception
{
    /**
     * @internal
     */
    function __construct($message)
    {
        parent::__construct($message);
    }
}
