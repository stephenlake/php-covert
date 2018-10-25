<?php

namespace Covert;

use Composer\Factory;
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

    public function plan($class)
    {
        $this->class = $class;

        if (!class_exists($this->class)) {
            throw new ClassNotFoundException("$class does not exist");
        }

        $this->autoloadPath = dirname(Factory::getComposerFile()) . '/vendor/autoload.php';

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

        $this->process = Nohup::run("php -r \"$autolod $include $newInst\"");

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
