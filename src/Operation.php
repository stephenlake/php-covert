<?php

declare(strict_types=1);

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
    private $autoload = '';

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
     * Command to run PHP.
     *
     * @var string
     */
    private $command = 'php';

    /**
     * Information is process running.
     *
     * @var bool
     */
    private $isRunning = null;

    /**
     * Create a new operation instance.
     *
     * @param null|int $processId
     *
     * @throws \Exception
     */
    public function __construct($processId = null)
    {
        try {
            // If we run UnitTests this will throw Exception.
            $this->setAutoloadFile(__DIR__.'/../../../autoload.php');
        } catch (Exception $e) {
            // Set it to false whene running UnitTests
            $this->setAutoloadFile(false);
        }

        $this->setLoggingFile(false);
        $this->processId = $processId;
    }

    /**
     * Statically create an instance of an operation from an existing
     * process ID.
     *
     * @param int $processId
     *
     * @throws \Exception
     *
     * @return self
     */
    public static function withId(int $processId): self
    {
        return new self($processId);
    }

    /**
     * Execute the process.
     *
     * @param \Closure $closure The anonymous function to execute.
     *
     * @throws \ReflectionException
     *
     * @return self
     */
    public function execute(Closure $closure): self
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'covert');
        $temporaryContent = '<?php'.PHP_EOL.PHP_EOL;

        if (!empty($this->autoload)) {
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
     * @throws \Exception
     *
     * @return int
     */
    private function executeFile(string $file): int
    {
        $this->isRunning = true;

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
     * @throws \Exception
     *
     * @return int
     */
    private function runCommandForWindows(string $file): int
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

        $cmd = 'START /b '.$this->getCommand()." {$file}";

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
        proc_close($handle);
        $pid = shell_exec('powershell.exe -Command "(Get-CimInstance -Class Win32_Process -Filter \'parentprocessid='.$pid.'\').processid"');

        return (int) $pid;
    }

    /**
     * Execute the shell process for the *nix platform.
     *
     * @param string $file The absolute path to the executing file.
     *
     * @return int
     */
    private function runCommandForNix(string $file): int
    {
        $cmd = $this->getCommand()." {$file} ";

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
     * @param string|bool $autoload The absolute path to autoload.php file
     *
     * @throws \Exception
     *
     * @return self
     */
    public function setAutoloadFile($autoload): self
    {
        if (is_string($autoload)) {
            if (!$autoload = realpath($autoload)) {
                throw new Exception("The autoload path '{$autoload}' doesn't exist.");
            }

            $this->autoload = $autoload;
        }

        return $this;
    }

    /**
     * Set a custom path to the output logging file.
     *
     * @param string|bool $logging The absolute path to the output logging file.
     *
     * @return self
     */
    public function setLoggingFile($logging): self
    {
        $this->logging = $logging;

        return $this;
    }

    /**
     * Get a custom path to the output logging file.
     *
     * @return string|bool
     */
    public function getLoggingFile()
    {
        return $this->logging;
    }

    /**
     * Get command to run PHP.
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set command to run PHP.
     *
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Get the process ID of the task running as a system process.
     *
     * @return int|null
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
    public function isRunning(): bool
    {
        /*
         * If we do not check it before or last time process was running,
         * check its current status, otherwise it was running and was ended.
         */
        if ($this->isRunning === null || $this->isRunning === true) {
            if ($processId = $this->getProcessId()) {
                if (OperatingSystem::isWindows()) {
                    $this->isRunning = !empty(shell_exec('powershell.exe -Command "Get-CimInstance -Class Win32_Process -Filter \'processid='.$processId.'\'"'));
                } else {
                    $this->isRunning = (bool) posix_getsid($processId);
                }
            }
        }

        return (bool) $this->isRunning;
    }

    /**
     * Kill the current operation process if it is running.
     *
     * @return self
     */
    public function kill(): self
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
