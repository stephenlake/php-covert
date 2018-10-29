<h6 align="center">
    <img src="https://github.com/stephenlake/php-covert/blob/master/docs/assets/php-covert-banner.png?v=2" width="300"/>
</h6>

<h6 align="center">
    Execute code as a background system process for Linux, Mac and Windows without relying on any external dependencies.
</h6>

<br>

# PHP Covert
**PHP Covert** makes running background tasks (including namespaced methods) in PHP a piece of cake without the need to install external software. Plan your operation and execute it instantly as a background process.

Made with ❤️ by [Stephen Lake](http://stephenlake.github.io/)

## Getting Started
Install the package via composer.

    composer require stephenlake/php-covert

Try it!

```php
use Covert\Operation;

$operation = new Operation();
$operation->setLoggingFile('log.txt');
$operation->execute(function() {
     $counter = 0;
     
     while($counter < 120) {
        echo "I have been running in the background for {$counter} seconds!".PHP_EOL;
        sleep(1);
     }
});
```
That's it. Your task is now running in the background as a process. Get the process ID with `$operation->getProcessID()`. Check out the [documentation](https://stephenlake.github.io/php-covert) for further usage and features.

## Under the hood
The goal is to run project code - to instantiate an actual class and execute long-running code as a background task, `shell_exec` runs commands, not code, however we can achieve this by instantiating Covert via the command line using PHP's CLI execution inside of a `shell_exec` call and including the vendor autoload file. 

Generally, it's good practice to have your scripts exit out when complete, however when running a piece of code from an abstract overview using Covert, this may not be possible, therefore when the execution is complete, Covert will again call itself to terminate the process by checking its file cache for the stored process ID and terminate it if it is in an idle state, otherwise clear it from file cache.

## License

This library is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
