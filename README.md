<h6 align="center">
    <img src="https://github.com/stephenlake/php-covert/blob/master/docs/assets/php-covert.png" width="300"/>
</h6>

<h6 align="center">
    Easily execute namespaced PHP methods in the background as a system process for Linux, Mac and Windoze.
</h6>

### Note: This project is brand new and under active development - it is NOT ready for production use and no methods have been finalised. Release and documentation will be published within the next few weeks.

<br>

# PHP Covert
**PHP Covert** makes running background tasks (including namespaced methods) in PHP a piece of cake. Plan your operation and execute it instantly as a background process, returning the process ID for your control.

Made with ❤️ by [Stephen Lake](http://stephenlake.github.io/)

## Getting Started
Install the package via composer.

    composer require stephenlake/php-covert

Try it!

```php
use Covert\Operation;

$operation = new Operation();
$operation->plan(\Your\Lengthy\Tasks\SomeSuperLengthyTask::class);
$operation->execute('LengthyClassMethodName');
```
That's it. Your task is now running in the background as a process. Get the process ID with `$operation->getProcessID()`. Check out the [documentation](https://stephenlake.github.io/php-covert) for further usage and features.

## License

This library is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.