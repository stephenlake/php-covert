<?php

declare(strict_types=1);

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
     * @throws \ReflectionException
     *
     * @return string
     */
    public static function toString(Closure $closure): string
    {
        $functionStringValue = '';
        $functionReflection = new ReflectionFunction($closure);

        $vars = $functionReflection->getStaticVariables();

        foreach ($vars as $name => $value) {
            $value = base64_encode(serialize($value));
            $functionStringValue .= '$'.$name.' = unserialize(base64_decode(\''.$value.'\'));'.PHP_EOL;
        }

        $file = file($functionReflection->getFileName());

        $lastLine = ($functionReflection->getEndLine() - 1);

        for ($codeLine = $functionReflection->getStartLine(); $codeLine < $lastLine; $codeLine++) {
            $functionStringValue .= $file[$codeLine];
        }

        return $functionStringValue;
    }
}
