<?php

namespace Covert;

use Covert\Internals\Exceptions\ClassNotFoundException;
use Covert\Internals\Exceptions\MethodNotFoundException;
use Covert\Internals\Nohup;
use Covert\Internals\Process;
use Covert\Internals\FileStore;

class Operation
{
    private $class;
    private $className;
    private $autoloadPath;
    private $process;
    private $fileStore;

    private $id;
    private $name;

    public function __construct()
    {
        $this->id = md5(microtime());
        $this->fileStore = new FileStore();
    }

    public function plan($class)
    {
        $this->class = $class;
        $this->name = $class;

        if (!class_exists($this->class)) {
            throw new ClassNotFoundException("$class does not exist");
        }

        $this->autoloadFrom(__DIR__ . '/../../../autoload.php');

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
        $include .= "use Covert\\Operation;";

        $newInst = "(new $this->className" . '())->' . "$method" . '();';

        $command = "php -r \"$autolod $include $newInst (new Operation())->terminate('{$this->id}'); exit(0);\"";

        $this->process = Nohup::run($command);

        $this->addProcess($this->process);

        return $this;
    }

    public function as($name)
    {
        $this->name = $name;

        return $this;
    }

    public function autoloadFrom($autoloadPath)
    {
        $this->autoloadPath = realpath($autoloadPath);

        if (!file_exists($this->autoloadPath)) {
            throw new Exception("The autoload path '{$this->autoloadPath}' doesn't exist.");
        }

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

        return $this;
    }

    private function addProcess($process)
    {
        $content = $this->fileStore->read();

        $content['processes']["{$this->id}"] = [
          'pid'   => $process->getPid(),
          'name'  => $this->name,
          'start' => time(),
          'state' => 'active'
        ];

        $this->fileStore->write($content);
    }

    private function removeProcess($id)
    {
        $content = $this->fileStore->read();

        $content['processes']["{$id}"] = '';

        $this->fileStore->write($content);
    }

    public function terminate($id)
    {
        $content = $this->fileStore->read();

        $pid = $content['processes']["$id"]['pid'];

        $process = Process::loadFromPid($pid);
        $process->stop();

        $this->removeProcess($id);
    }

    public function history()
    {
        $content = $this->fileStore->read();

        return $content['processes'];
    }

    public function removeAll()
    {
        $content = $this->fileStore->read();

        foreach ($content['processes'] as $id => $value) {
            $this->terminate($id);
        }

        return $this->history();
    }
}
