<?php

namespace Covert;

use Closure;
use Covert\Utils\FunctionReflection;
use Covert\Utils\OperatingSystem;
use Exception;

class Operation
{
    /**
     * The absolute path to the autoload.php file.
     *
     * @var string
     */
    private $autoload;

    /**
     * The absolute path to the output log file.
     *
     * @var bool|string
     */
    private $logging;

    /**
     * The process ID (pid) of the background task.
     *
     * @var int |null
     */
    private $processId;

    /**
     * Create a new operation instance.
     *
     * @return self
     */
    public function __construct()
    {
        $this->autoload = __DIR__.'/../../../autoload.php';
        $this->logging = false;
        $this->processId = null;
    }

    /**
     * Execute the process.
     *
     * @param \Closure $closure The anonyouse function to execute.
     *
     * @return void
     */
    public function execute(Closure $closure)
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'covert');

        $temporaryContent = '<?php'.PHP_EOL.PHP_EOL;

        if ($this->autoload !== false) {
            $temporaryContent .= "require('$this->autoload');".PHP_EOL.PHP_EOL;
        }

        $temporaryContent .= FunctionReflection::toString($closure).PHP_EOL.PHP_EOL;
        $temporaryContent .= 'unlink(__FILE__);'.PHP_EOL.PHP_EOL;
        $temporaryContent .= 'exit;';

        file_put_contents($temporaryFile, $temporaryContent);

        $this->processId = $this->executeFile($temporaryFile);

        return $this;
    }

    /**
     * Check the operating system call appropriate execution method.
     *
     * @param string $file The absolute path to the executing file.
     *
     * @return void
     */
    private function executeFile($file)
    {
        if (OperatingSystem::isWindows()) {
            return $this->runCommandForWindows($file);
        }

        return $this->runCommandForNix($file);
    }

    /**
     * Execute the shell process for the Windows platform.
     *
     * @param string $file The absolute path to the executing file.
     *
     * @return void
     */
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
            $stderrPipe,
        ];

        $cmd = "START /b php {$file}";

        $handle = proc_open(
            $cmd,
            $desc,
            $pipes,
            getcwd()
        );

        if (!is_resource($handle)) {
            throw new Exception('Could not create a background resource. Try using a better operating system.');
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

    /**
     * Execute the shell process for the *nix platform.
     *
     * @param string $file The absolute path to the executing file.
     *
     * @return void
     */
    private function runCommandForNix($file)
    {
        $cmd = "php {$file} ";

        if (!$this->logging) {
            $cmd .= '> /dev/null 2>&1 & echo $!';
        } else {
            $cmd .= "> {$this->logging} & echo $!";
        }

        return (int) shell_exec($cmd);
    }

    /**
     * Set a custom path to the autoload.php file.
     *
     * @param string $file The absolute path to the autoload.php file.
     *
     * @return void
     */
    public function setAutoloadFile($autoload)
    {
        if ($autload !== false && !file_exists($autoload)) {
            throw new Exception("The autoload path '{$autoload}' doesn't exist.");
        }

        $this->autoload = $autoload;

        return $this;
    }

    /**
     * Set a custom path to the output logging file.
     *
     * @param string $file The absolute path to the output logging file.
     *
     * @return void
     */
    public function setLoggingFile($logging)
    {
        $this->logging = $logging;

        return $this;
    }

    /**
     * Get the process ID of the task running as a system process.
     *
     * @return int
     */
    public function getProcessId()
    {
        return $this->processId;
    }
}
