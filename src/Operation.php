<?php

namespace Covert;

use Covert\Internals\Exceptions\ClassNotFoundException;
use Covert\Internals\Exceptions\MethodNotFoundException;
use Covert\Internals\Nohup;
use Covert\Internals\Process;

class Operation
{
    private $class;
    private $className;
    private $autoloadPath;
    private $process;

    const KEEP_ALIVE = 0;
    const FREE_WHEN_DONE = 1;

    public function plan($class, $after = 1)
    {
        $this->class = $class;

        if (!class_exists($this->class)) {
            throw new ClassNotFoundException("$class does not exist");
        }

        $this->autoloadPath = realpath(__DIR__ . '/../../../autoload.php');

        if (!file_exists($this->autoloadPath)) {
            throw new Exception("Seems that we can't find your composer autoload.php file, are you using a custom setup?");
        }

        $this->classSpace = $class;
        $this->className = join('', array_slice(explode('\\', $class), -1));

        return $this;
    }

    public function execute($method)
    {
        if (!method_exists($this->class, $method)) {
            throw new MethodNotFoundException("$method does not exist on class $class");
        }

        $autolod = "require('$this->autoloadPath');";
        $include = "use $this->classSpace;";
        $newInst = "(new $this->className" . '())->' . "$method" . '();';
        $command = "php -r \"$autolod $include $newInst\"";

        $this->process = Nohup::run($command);

        return $this;
    }

    public function isRunning()
    {
        return $this->process->isRunning();
    }

    public function getProcess()
    {
        return $this->process;
    }

    public function getProcessID()
    {
        return $this->process->getPid();
    }

    public function loadFromProcessID($processId)
    {
        $this->process = Process::loadFromPid($processId);

        return $this->getProcess();
    }

    public function stop()
    {
        $this->process->stop();
    }
}
