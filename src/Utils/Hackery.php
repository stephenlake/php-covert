<?php

namespace Covert\Utils;

use Closure;

class Hackery
{
    public static function closureToString(Closure $closure)
    {
        $functionStringValue = '';
        $functionReflection = new \ReflectionFunction($closure);

        $file = file($functionReflection->getFileName());

        $lastLine = ($functionReflection->getEndLine()-1);

        for ($codeline = $functionReflection->getStartLine(); $codeline < $lastLine; $codeline++) {
            $functionStringValue .= $file[$codeline];
        }

        return $functionStringValue;
    }
}
