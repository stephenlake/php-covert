<?php

namespace Covert\Internals;

use Covert\Operation;

class InternalOperation
{
    public function __construct($namespace, $method, $id)
    {
        $this->execute($namespace, $method);
        $this->terminate($id);
    }

    private function execute($namespace, $method)
    {
        $instance = new $namespace();
        $instance->$method();
    }

    private function terminate($id)
    {
        $operation = new Operation();
        $operation->terminate($id);
    }
}
