<?php

namespace Covert\Utils;

class OperatingSystem
{
    public static function isWindows()
    {
        return substr(strtoupper(PHP_OS), 0, 3) === 'WIN';
    }
}
