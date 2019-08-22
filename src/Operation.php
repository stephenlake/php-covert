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
     * @var int|null
     */
    private $processId;

    /**
     * Create a new operation instance.
     *
     * @param null $processId
     *
     * @throws \Exception
     */
    public function __construct($processId = null)
    {
        $this->setAutoloadFile(__DIR__.'/../../../autoload.php');
        $this->setLoggingFile(false);
        $this->processId = $processId;
    }

    /**
     * Statically create an instance of an operation from an existing
     * process ID.
     *
     * @param $processId
     *
     * @return self
     * @throws \Exception
     */
    public static function withId($processId)
    {
        return new self($processId);
    }

    /**
     * Execute the process.
     *
     * @param \Closure $closure The anonymous function to execute.
     *
     * @return self
     * @throws \ReflectionException
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
     * @return integer
     * @throws \Exception
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
     * @return integer
     * @throws \Exception
     */
    private function runCommandForWindows($file)
    {
        if ($this->getLoggingFile()) {
            $stdoutPipe = ['file', $this->getLoggingFile(), 'w'];
            $stderrPipe = ['file', $this->getLoggingFile(), 'w'];
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
            [],
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
     * @return integer
     */
    private function runCommandForNix($file)
    {
        $cmd = "php {$file} ";

        if (!$this->getLoggingFile()) {
            $cmd .= '> /dev/null 2>&1 & echo $!';
        } else {
            $cmd .= "> {$this->getLoggingFile()} & echo $!";
        }

        return (int) shell_exec($cmd);
    }

    /**
     * Set a custom path to the autoload.php file.
     *
     * @param $autoload
     *
     * @return self
     * @throws \Exception
     */
    public function setAutoloadFile($autoload)
    {
        if ($autoload !== false && !file_exists($autoload)) {
            throw new Exception("The autoload path '{$autoload}' doesn't exist.");
        }

        $this->autoload = realpath($autoload);

        return $this;
    }

    /**
     * Set a custom path to the output logging file.
     *
     * @param string|bool $logging The absolute path to the output logging file.
     *
     * @return self
     */
    public function setLoggingFile($logging)
    {
        $this->logging = $logging;

        return $this;
    }

    /**
     * Get a custom path to the output logging file.
     *
     * @return string|boolean
     */
    public function getLoggingFile()
    {
        return $this->logging;
    }

    /**
     * Get the process ID of the task running as a system process.
     *
     * @return integer|null
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * Returns true if the process ID is still active.
     *
     * @return bool
     */
    public function isRunning()
    {
        $processId = $this->getProcessId();

        if (OperatingSystem::isWindows()) {
            $pids = shell_exec("wmic process get processid | find \"{$processId}\"");
            $resource = array_filter(explode(' ', $pids));

            $isRunning = count($resource) > 0 && $processId == reset($resource);
        } else {
            $isRunning = (bool) posix_getsid($processId);
        }

        return $isRunning;
    }

    /**
     * Kill the current operation process if it is running.
     *
     * @return self
     */
    public function kill()
    {
        if ($this->isRunning()) {
            $processId = $this->getProcessId();

            if (OperatingSystem::isWindows()) {
                $cmd = "taskkill /pid {$processId} -t -f";
            } else {
                $cmd = "kill -9 {$processId}";
            }

            shell_exec($cmd);
        }

        return $this;
    }
}
