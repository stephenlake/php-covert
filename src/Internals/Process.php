<?php

namespace Covert\Internals;

class Process
{
    protected $pid;

    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function isRunning()
    {
        if (OperatingSystem::isWin()) {
            $cmd = "wmic process get processid | find \"{$this->pid}\"";
            $res = array_filter(explode(" ", shell_exec($cmd)));
            return count($res) > 0 && $this->pid == reset($res);
        } else {
            return !!posix_getsid($this->pid);
        }
    }

    public function stop()
    {
        if (OperatingSystem::isWin()) {
            $cmd = "taskkill /pid {$this->pid} -t -f";
        } else {
            $cmd = "kill -9 {$this->pid}";
        }
        shell_exec($cmd);
    }

    public static function loadFromPid($pid)
    {
        return new static($pid);
    }
}
