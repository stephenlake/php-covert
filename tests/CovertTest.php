<?php

namespace Covert\Tests\Unit;

use Covert\Operation;
use PHPUnit\Framework\TestCase;

class CovertTest extends TestCase
{
    public function testProcessStarts()
    {
        $operation = new Operation();

        $this->assertFalse($operation->isRunning(), 'Process is running, while is not been executed yet.');

        $operation->execute(function () {
            sleep(5);
        });

        $this->assertTrue(!is_null($operation->getProcessId()), 'Process ID was not found, while process is running.');
        $this->assertTrue($operation->isRunning(), 'Process is not running, while it should be running.');
    }

    public function testProcessProducesLogFile()
    {
        $operation = new Operation();
        $operation->setLoggingFile(($loggingFile = sys_get_temp_dir().'/log.txt'));
        $operation->execute(function () {
            $counter = 0;

            while ($counter < 10) {
                $counter++;
                sleep(1);
                echo "I have been running in the background for {$counter} seconds!".PHP_EOL;
            }
        });

        $loggingFileExists = file_exists($loggingFile);

        $this->assertTrue($loggingFileExists, 'Log file does NOT exists, while it should exists.');

        sleep(4);

        $loggingFileHasContent = count(file($loggingFile)) > 2;

        $this->assertTrue($loggingFileHasContent, 'Log file does NOT have any content, while it should have some.');

        unlink($loggingFile);
    }

    public function testProcessTerminatesWhenDone()
    {
        $operation = new Operation();
        $operation->execute(function () {
            sleep(2);
        });

        $this->assertTrue($operation->isRunning(), 'Process is not running, while it should be running.');

        sleep(4);

        $this->assertFalse($operation->isRunning(), 'Process is running, while it should be ended.');
    }

    public function testProcessTerminatesManually()
    {
        $operation = new Operation();
        $operation->execute(function () {
            sleep(30);
        });

        $thatOperation = Operation::withId($operation->getProcessId());

        $this->assertTrue($thatOperation->isRunning(), 'Process is not running, while it should be running.');

        $thatOperation->kill();

        sleep(1);

        $this->assertFalse($thatOperation->isRunning(), 'Process is running ,while it should be terminated manually.');
    }

    public function testProcessHandlePassedVariables()
    {
        $operation = new Operation();
        $operation->setLoggingFile(($loggingFile = sys_get_temp_dir().'/log.txt'));
        $test = 'TEST';
        $operation->execute(function () use ($test) {
            echo $test;
        });

        sleep(1);
        $result = file_get_contents($loggingFile);
        $this->assertSame($result, $test, 'Test value was not handled properly.');
        unlink($loggingFile);

        $test = '"TEST"'."'Test'";
        $operation->execute(function () use ($test) {
            echo $test;
        });

        sleep(1);
        $result = file_get_contents($loggingFile);
        $this->assertSame($result, $test, 'Test value was not handled properly.');
        unlink($loggingFile);
    }

    public function testCommand()
    {
        $operation = new Operation();

        $operation->setCommand('php -d memory_limit="256M"');

        $this->assertEquals('php -d memory_limit="256M"', $operation->getCommand(), 'Command was not set properly.');
    }
}
