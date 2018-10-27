<?php

namespace Covert;

use Covert\Internals\Exceptions\ClassNotFoundException;
use Covert\Internals\Exceptions\MethodNotFoundException;
use Covert\Internals\FileStore;
use Covert\Internals\OperatingSystem;

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
        $theCommand .= '" ';

        if (substr(strtoupper(PHP_OS), 0, 3) === 'WIN') {
            $theCommand .= "&";
        } else {
            $theCommand .= "> /dev/null 2>&1 & echo $!";
        }

        $this->process = (int) shell_exec($theCommand);

        $this->addProcess();

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

    private function addProcess()
    {
        $content = $this->fileStore->read();

        $data = [
          'pid'   => $this->process,
          'name'  => $this->name,
          'start' => time(),
          'state' => 'active'
        ];

        $content['processes']["{$this->id}"] = $data;

        $this->fileStore->write($content);
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

            if ($this->isRunning($pid)) {
                $this->stop($pid);
            }

            $this->removeProcess($id);
        }
    }

    public function list()
    {
        $content = $this->fileStore->read();

        foreach ($content['processes'] as $id => $process) {
            if (!$this->isRunning($process['pid'])) {
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

        return $this->list();
    }

    public function isRunning($pid)
    {
        if (substr(strtoupper(PHP_OS), 0, 3) === 'WIN') {
            $res = array_filter(explode(" ", shell_exec("wmic process get processid | find \"{$pid}\"")));
            return count($res) > 0 && $pid == reset($res);
        } else {
            return !!posix_getsid($pid);
        }
    }

    public function stop($pid)
    {
        if (substr(strtoupper(PHP_OS), 0, 3) === 'WIN') {
            $cmd = "taskkill /pid {$pid} -t -f";
        } else {
            $cmd = "kill -9 {$pid}";
        }

        shell_exec($cmd);
    }
}
