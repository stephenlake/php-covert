<?php

namespace Covert\Utils;

class OperatingSystem
{
    /**
     * Returns true if the operating system is Windows.
     *
     * @return bool
     */
    public static function isWindows()
    {
        return substr(strtoupper(PHP_OS), 0, 3) === 'WIN';
    }
}
