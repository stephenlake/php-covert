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

## Output Logging
@TODO

## Specify Custom Autoload File
@TODO

## Disable Autoload File
@TODO

## Important Caveats
Covert runs background tasks as a new separate PHP process for each operation executed, because of this it is not aware of namespaced imports and currently cannot figure out which classes belong to which namespace, therefore when defining the anonymous function, it's important to remember to use classes' fully qualified namespace otherwise the process will fail, for example:

This will **succeed**:
```
$operation->execute(function() {
     $instance = new \Some\Awesome\Namespace\SomeClass();
     $instance->doSomething();
});
```
But this will **fail**:
```
use Some\Awesome\Namespace\SomeClass;

$operation->execute(function() {
     $instance = new SomeClass();
     $instance->doSomething();
});
```
