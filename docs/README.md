<h6 align="center">
    <img src="https://raw.githubusercontent.com/stephenlake/php-covert/master/docs/assets/php-covert-banner.png?v=2" width="300"/>
</h6>

<h6 align="center">
    Execute code as a background system process for Linux, Mac and Windows without relying on any external dependencies.
</h6>

# Getting Started

## Install via Composer

Install the package via composer.

    composer require stephenlake/php-covert

# Usage

## Example

Create a basic task that echoes a line every second 120 times:

```php
use Covert\Operation;

$operation = new Operation();
$operation->execute(function() {
    $counter = 0;

    while($counter < 120) {
        $counter++;
        sleep(1);
        echo "I have been running in the background for {$counter} seconds!".PHP_EOL;
    }
});
```

When an operation instance is executed, the system process ID is assigned to it which may be retrieved using `$operation->getProcessId()`, with this you can call a few helper functions:

### Check Operation Status

If you already have the operation instantiation available, you may call the following to check whether it is running or not:

```php
$operation->isRunning()
```

Additionally, if you do not have the instantiation available, but know the process ID of the process, you may instantiate the existing process using:

```php
use Covert\Operation;

$existingOperation = Operation::withId($processId);
$existingOperation->isRunning();
```

### Terminate Operation

If for some reason you need to terminate a process before it has finished running you may do so:

```php
use Covert\Operation;

$existingOperation = Operation::withId($processId);
$existingOperation->kill();
```

## Pass variable to script

You can pass some variables to Your script if You need to. To do so just execute Your function with inheriting variables from the parent scope:

```php
use Covert\Operation;

$operation = new Operation();
$nominator = 1;
$denominator = 2
$operation->execute(function() use ($nominator, $denominator) {
    echo $nominator / $denominator;
});
``` 

## Output Logging

By default, Covert will not store any process output. You may set a logging directory on the operation using `$operation->setLoggingPath('path/to/your/log.text')`.

```php
use Covert\Operation;

$operation = new Operation();
$operation->setLoggingFile('path/to/your/log.text');
$operation->execute(function() {
    echo "This will be saved to log.";
});
```

_Note:_ Ensure that the path to your log file is writeable!

## Specify Custom Autoload File

Covert assumes that you want to autoload your composer dependencies to make use of namespaced instantiations, for example, you may call a new class inside of your execution:
```php
use Covert\Operation;

$operation = new Operation();
$operation->execute(function() {
     $instance = new \Some\Awesome\Namespace\SomeClass();
     $instance->doSomething();
});
```
However there may be instances where your `autoload.php` file is not in the same path as Covert expects it to be, if this is the case, you may define the custom file using:
```php
use Covert\Operation;

$operation = new Operation();
$operation->setAutoloadFile('path/to/your/autoload.php');
```

## Disable Autoload File
If you are using flat PHP without composer or do not wish to load any packages, you can disable the inclusion of the `autoload.php` file with:
```php
use Covert\Operation;

$operation = new Operation();
$operation->setAutoloadFile(false);
```

## Specify custom command to run PHP
You may need to run the PHP interpreter with a command other than 'php' or set some parameters when executing PHP. In this case You can set custom command to run it:
```php
use Covert\Operation;

$operation = new Operation();
$operation->setCommand('/usr/local/bin/php73 -d memory_limit="512M"');
```

# Important Caveats

Covert runs background tasks as a new separate PHP process for each operation executed, because of this it is not aware of namespaced imports and currently cannot figure out which classes belong to which namespace, therefore when defining the anonymous function, it's important to remember to use classes' fully qualified namespace otherwise the process will fail, for example:

This will **succeed**:
```php
use Covert\Operation;

$operation = new Operation();
$operation->execute(function() {
     $instance = new \Some\Awesome\Namespace\SomeClass();
     $instance->doSomething();
});
```
But this will **fail**:
```php
use Covert\Operation;
use Some\Awesome\Namespace\SomeClass;

$operation = new Operation();
$operation->execute(function() {
     $instance = new SomeClass();
     $instance->doSomething();
});
```