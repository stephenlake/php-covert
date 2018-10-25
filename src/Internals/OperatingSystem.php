<?php

namespace Covert\Internals;

class OperatingSystem
{
    public static function isWin()
    {
        return substr(strtoupper(PHP_OS), 0, 3) === 'WIN';
    }
}
