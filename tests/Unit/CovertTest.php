<?php

namespace Covert\Tests\Unit;

use Covert\Operation;
use Covert\Tests\TestCase;

class CovertTest extends TestCase
{
    public function testProcessStarts()
    {
        $operation = new Operation();
        $operation->setAutoloadFile(false);
        $operation->execute(function () {
            sleep(5);
        });

        $this->assertTrue(!is_null($operation->getProcessId()));
        $this->assertTrue($operation->isRunning());
    }

    public function testProcessProducesLogFile()
    {
        $operation = new Operation();
        $operation->setAutoloadFile(false);
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

        $this->assertTrue($loggingFileExists);

        sleep(4);

        $loggingFileHasContent = count(file($loggingFile)) > 2;

        $this->assertTrue($loggingFileHasContent);
    }

    public function testProcessTerminatesWhenDone()
    {
        $operation = new Operation();
        $operation->setAutoloadFile(false);
        $operation->execute(function () {
            sleep(2);
        });

        $this->assertTrue($operation->isRunning());

        sleep(4);

        $this->assertTrue(!$operation->isRunning());
    }
}
