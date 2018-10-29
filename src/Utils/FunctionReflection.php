<?php

namespace Covert\Utils;

use Closure;
use ReflectionFunction;

class FunctionReflection
{
    /**
     * Get the string representation of the anonymous function.
     *
     * @param Closre $closure The anonymous function.
     *
     * @return string
     */
    public static function toString(Closure $closure)
    {
        $functionStringValue = '';
        $functionReflection = new ReflectionFunction($closure);

        $file = file($functionReflection->getFileName());

        $lastLine = ($functionReflection->getEndLine()-1);

        for ($codeline = $functionReflection->getStartLine(); $codeline < $lastLine; $codeline++) {
            $functionStringValue .= $file[$codeline];
        }

        return $functionStringValue;
    }
}
