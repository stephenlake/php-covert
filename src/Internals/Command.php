<?php

namespace Covert\Internals;

class Command
{
    protected $commandline;
    protected $outputFile;
    protected $errLogFile;

    public function __construct($commandLine, $outputFile, $errlogFile)
    {
        $this->commandline = $commandLine;

        $this->setOutputFile($outputFile);
        $this->setErrlogFile($errlogFile);
    }

    public function __toString()
    {
        return $this->commandline;
    }

    public function setOutputFile($outputFile)
    {
        $this->outputFile = (string) $outputFile;
    }

    public function getOutputFile()
    {
        return $this->outputFile;
    }

    public function setErrlogFile($errlogFile)
    {
        $this->errLogFile = (string) $errlogFile;
    }

    public function getErrlogFile()
    {
        return $this->errLogFile;
    }

    public function getCommandLine()
    {
        if (OS::isWin()) {
            $command = "START /b {$this->commandline}";
        } else {
            $command = '{(' . $this->commandline . ') <&3 3<&- 3>/dev/null & } 3<&0;';
            $command .= 'pid=$!;echo $pid >&3; wait $pid; code=$?; echo $code >&3;exit $code';
        }

        return $command;
    }

    public function nohup()
    {
        return Nohup::runCommand($this);
    }
}
