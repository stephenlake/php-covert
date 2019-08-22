<?php

namespace Covert\Utils;

use Closure;
use ReflectionFunction;

class FunctionReflection
{
    /**
     * Get the string representation of the anonymous function.
     *
     * @param \Closure $closure The anonymous function.
     *
     * @return string
     * @throws \ReflectionException
     */
    public static function toString(Closure $closure)
    {
        $functionStringValue = '';
        $functionReflection = new ReflectionFunction($closure);

        $file = file($functionReflection->getFileName());

        $lastLine = ($functionReflection->getEndLine() - 1);

        for ($codeLine = $functionReflection->getStartLine(); $codeLine < $lastLine; $codeLine++) {
            $functionStringValue .= $file[$codeLine];
        }

        return $functionStringValue;
    }
}
