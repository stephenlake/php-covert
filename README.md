<h6 align="center">
    <img src="https://raw.githubusercontent.com/stephenlake/php-covert/master/docs/assets/php-covert-banner.png" width="300"/>
</h6>

<h6 align="center">
    Execute code as a background system process for Linux, Mac and Windows without relying on any external dependencies.
</h6>

<p align="center">
<a href="https://travis-ci.org/stephenlake/php-covert"><img src="https://img.shields.io/travis/stephenlake/php-covert/master.svg?style=flat-square" alt="Build Status"></a>
<a href="https://github.styleci.io/repos/154746678"><img src="https://github.styleci.io/repos/154746678/shield?branch=master&style=flat-square" alt="StyleCI"></a>
<a href="https://scrutinizer-ci.com/g/stephenlake/php-covert"><img src="https://img.shields.io/scrutinizer/g/stephenlake/php-covert.svg?style=flat-square" alt=""></a>
<a href="https://packagist.org/packages/stephenlake/php-covert">
<img src="https://img.shields.io/packagist/dt/stephenlake/php-covert.svg?style=flat-square" alt="">
</a>  
<a href="https://github.com/stephenlake/php-covert"><img src="https://img.shields.io/github/release/stephenlake/php-covert.svg?style=flat-square" alt="Release"></a>
<a href="https://github.com/stephenlake/php-covert/LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square" alt="License"></a>
</p>

<br>

# PHP Covert
**PHP Covert** makes running inline code as background tasks in PHP a piece of cake without the need to install external software nor enable additional extensions. Plan your operation and execute it instantly as a background process.

Made with ❤️ by [Stephen Lake](http://stephenlake.github.io/). Maintained with ❤️ by [Paweł Kłopotek-Główczewski](https://github.com/pawelkg).

## Disclaimer
This package does not make use of threading and is **not** intended to replace queues/workers, it's more of a hack than anything and is not the 'proper' way to schedule tasks nor run them in the background. This package was created as an experiment and published due to the frequently asked questions of 'how to run a PHP task in the background'.

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
        $counter++;
        sleep(1);
        echo "I have been running in the background for {$counter} seconds!".PHP_EOL;
     }
});

// Continue with your app's logic here while your background task is running
```
That's it. Your task is now running in the background as a process. Get the process ID with `$operation->getProcessID()`. Check out the [documentation](https://stephenlake.github.io/php-covert) for further usage and features.

## License

This library is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
