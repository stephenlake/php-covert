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

        $theCommand = 'php -r "';
        $theCommand .= "require('$this->autoloadPath');";
        $theCommand .= "use Covert\\Internals\\InternalOperation;";
        $theCommand .= "new InternalOperation('$this->classSpace', '$method', '$this->id');";
        $theCommand .= '"';

        $this->process = Nohup::run($theCommand);

        $this->addProcess($this->process);

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

        $data = [
          'pid'   => $process->getPid(),
          'name'  => $this->name,
          'start' => time(),
          'state' => 'active'
        ];

        if ($process->isRunning()) {
            $content['processes']["{$this->id}"] = $data;

            $this->fileStore->write($content);
        }

        return;
    }

    private function removeProcess($id)
    {
        $content = $this->fileStore->read();

        unset($content['processes']["{$id}"]);

        $this->fileStore->write($content);
    }

    public function terminate($id)
    {
        $content = $this->fileStore->read();

        if (isset($content['processes']["$id"]['pid'])) {
            $pid = $content['processes']["$id"]['pid'];

            $process = Process::loadFromPid($pid);
            $process->stop();

            $this->removeProcess($id);
        }
    }

    public function list()
    {
        $content = $this->fileStore->read();

        foreach ($content['processes'] as $id => $process) {
            $process = Process::loadFromPid($process['pid']);

            if (!$process->isRunning()) {
                $this->terminate($id);
            }
        }

        return $this->fileStore->read()['processes'];
    }

    public function removeAll()
    {
        $content = $this->fileStore->read();

        foreach ($content['processes'] as $id => $value) {
            try {
                $this->terminate($id);
            } catch (\Exception $e) {
            }
        }

        return $this->history();
    }
}
