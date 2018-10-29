<?php

namespace Covert\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setup()
    {
        parent::setup();

        $this->app->setBasePath(__DIR__.'/../');
    }

    protected function getEnvironmentSetUp($app)
    {
    }
}
