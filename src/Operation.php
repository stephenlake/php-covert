<?php

namespace Covert;

use Covert\Utils\OperatingSystem;
use Covert\Utils\Hackery;
use Closure;
use Exception;

class Operation
{
    private $autoload;
    private $logging;
    private $processId;

    public function __construct()
    {
        $this->autoload = __DIR__ . '/../../../autoload.php';
        $this->logging = false;
        $this->processId = null;
    }

    public function execute(Closure $closure)
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'covert');

        $temporaryContent = '<?php'.PHP_EOL.PHP_EOL;
        $temporaryContent .= "require('$this->autoload');".PHP_EOL.PHP_EOL;
        $temporaryContent .= Hackery::closureToString($closure).PHP_EOL.PHP_EOL;
        $temporaryContent .= "unlink(__FILE__);".PHP_EOL.PHP_EOL;
        $temporaryContent .= "exit;";

        file_put_contents($temporaryFile, $temporaryContent);

        $this->processId = $this->executeFile($temporaryFile);

        return $this;
    }

    private function executeFile($file)
    {
        if (OperatingSystem::isWindows()) {
            return $this->runCommandForWindows($file);
        }

        return $this->runCommandForNix($file);
    }

    private function runCommandForWindows($file)
    {
        if ($this->logging) {
            $stdoutPipe = ['file', $this->logging, 'w'];
            $stderrPipe = ['file', $this->logging, 'w'];
        } else {
            $stdoutPipe = fopen('NUL', 'c');
            $stderrPipe = fopen('NUL', 'c');
        }

        $desc = [
            ['pipe', 'r'],
            $stdoutPipe,
            $stderrPipe
        ];

        $cmd = "START /b php {$file}";

        $handle = proc_open(
            $cmd,
            $desc,
            $pipes,
            getcwd()
        );

        if (!is_resource($handle)) {
            throw new \Exception('Could not create a background resource. Try using a better operating system.');
        }

        $pid = proc_get_status($handle)['pid'];

        try {
            proc_close($handle);
            $resource = array_filter(explode(' ', shell_exec("wmic process get parentprocessid, processid | find \"$pid\"")));
            array_pop($resource);
            $pid = end($resource);
        } catch (Exception $e) {
        }

        return $pid;
    }

    private function runCommandForNix($file)
    {
        $cmd = "php {$file} ";

        if (!$this->logging) {
            $cmd .= "> /dev/null 2>&1 & echo $!";
        } else {
            $cmd .= "> {$this->logging} & echo $!";
        }

        return (int) shell_exec($cmd);
    }

    public function setAutoloadFile($autoload)
    {
        if (!file_exists($autoload)) {
            throw new Exception("The autoload path '{$autoload}' doesn't exist.");
        }

        $this->autoload = $autoload;

        return $this;
    }

    public function setLoggingFile($logging)
    {
        $this->logging = $logging;

        return $this;
    }

    public function getProcessId()
    {
        return $this->processId;
    }
}
