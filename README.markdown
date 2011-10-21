# Predis #

Predis is a flexible and feature-complete PHP (>= 5.3) client library for the Redis key-value store.

For a list of frequently asked questions about Predis, see the __FAQ__ file in the root of the repository.
For a version compatible with PHP 5.2 you must use the backported version from the latest release in the 0.6.x series.


## Main features ##

- Complete support for Redis from __1.2__ to __2.4__ and the current development versions using different server profiles.
- Client-side sharding with support for consistent hashing or custom distribution strategies.
- Command pipelining on single and aggregated connections.
- Abstraction for Redis transactions (Redis >= 2.0) with support for CAS operations (Redis >= 2.2).
- Ability to connect to Redis using TCP/IP or UNIX domain sockets with optional support for persistent connections.
- Connections to Redis instances are automatically and lazily estabilished upon the first call to a command.
- Flexible system to define and register your own set of commands to a client instance.


## Quick examples ##

See the [official wiki](http://wiki.github.com/nrk/predis) of the project for a more
complete coverage of all the features available in Predis.


### Loading Predis

Predis relies on the autoloading features of PHP and complies with the
[PSR-0 standard](http://groups.google.com/group/php-standards/web/psr-0-final-proposal)
for interoperability with most of the major frameworks and libraries.

When used in a project or script without PSR-0 autoloading, Predis includes its own autoloader for you to use:

``` php
<?php
require PREDIS_BASE_PATH . '/Autoloader.php';

Predis\Autoloader::register();

// You can now create a Predis\Client, and use other Predis classes, without
// requiring any additional files.
```

You can also create a single [Phar](http://www.php.net/manual/en/intro.phar.php) archive from the repository
just by launching the `createPhar.php` script located in the `bin` directory. The generated Phar ships with
a stub that defines an autoloader function for Predis, so you just need to require the Phar archive in order
to be able to use the library.

Alternatively you can generate a single PHP file that holds every class, just like older versions of Predis,
using the `createSingleFile.php` script located in the `bin` directory. In this way you can load Predis in
your scripts simply by using functions such as `require` and `include`, but this practice is not encouraged.


### Connecting to a local instance of Redis ###

You don't have to specify a tcp host and port when connecting to Redis instances running on the
localhost on the default port:

``` php
<?php
$redis = new Predis\Client();
$redis->set('foo', 'bar');
$value = $redis->get('foo');
```

You can also use an URI string or an array-based dictionary to specify the connection parameters:

``` php
<?php
$redis = new Predis\Client('tcp://10.0.0.1:6379');

// is equivalent to:

$redis = new Predis\Client(array(
    'scheme' => 'tcp',
    'host'   => '10.0.0.1',
    'port'   => 6379,
));
```


### Pipelining multiple commands to multiple instances of Redis with client-side sharding ###

Pipelining helps with performances when there is the need to issue many commands to a server
in one go. Furthermore, pipelining works transparently even on aggregated connections. Predis,
in fact, supports client-side sharding of data using consistent-hashing on keys and clustered
connections are supported natively by the client class.

``` php
<?php
$redis = new Predis\Client(array(
    array('host' => '10.0.0.1', 'port' => 6379),
    array('host' => '10.0.0.2', 'port' => 6379)
));

$replies = $redis->pipeline(function($pipe) {
    for ($i = 0; $i < 1000; $i++) {
        $pipe->set("key:$i", str_pad($i, 4, '0', 0));
        $pipe->get("key:$i");
    }
});
```


### Overriding standard connection classes with custom ones ###

Predis allows developers to create new connection classes to add support for new protocols
or override the existing ones to provide a different implementation compared to the default
classes. This can be obtained by subclassing the `Predis\Network\IConnectionSingle` interface.

``` php
<?php
class MyConnectionClass implements Predis\Network\IConnectionSingle
{
    // implementation goes here
}

// Let Predis automatically use your own class to handle the default TCP connection
Predis\ConnectionFactory::define('tcp', 'MyConnectionClass');
```


You can have a look at the `Predis\Network` namespace for some actual code that gives a better
insight about how to create new connection classes.


### Definition and runtime registration of new commands on the client ###

Let's suppose Redis just added the support for a brand new feature associated
with a new command. If you want to start using the above mentioned new feature
right away without messing with Predis source code or waiting for it to find
its way into a stable Predis release, then you can start off by creating a new
class that matches the command type and its behaviour and then bind it to a
client instance at runtime. Actually, it is easier done than said:

``` php
<?php
class BrandNewRedisCommand extends Predis\Commands\Command
{
    public function getId()
    {
        return 'NEWCMD';
    }
}

$redis = new Predis\Client();
$redis->getProfile()->defineCommand('newcmd', 'BrandNewRedisCommand');
$redis->newcmd();
```


## Development ##

Predis is fully backed up by a test suite which tries to cover all the aspects of the
client library and the interaction of every single command with a Redis server. If you
want to work on Predis, it is highly recommended that you first run the test suite to
be sure that everything is OK, and report strange behaviours or bugs.

When modifying Predis please be sure that no warnings or notices are emitted by PHP
by running the interpreter in your development environment with the `error_reporting`
variable set to `E_ALL | E_STRICT`.

The recommended way to contribute to Predis is to fork the project on GitHub, create
new topic branches on your newly created repository to fix or add features and then
open a new pull request with a description of the applied changes. Obviously, you can
use any other Git hosting provider of your preference. Diff patches will be accepted
too, even though they are not the preferred way to contribute to Predis.


## Dependencies ##

- PHP >= 5.3.0
- PHPUnit >= 3.5.0 (needed to run the test suite)

## Links ##

### Project ###
- [Source code](http://github.com/nrk/predis/)
- [Wiki](http://wiki.github.com/nrk/predis/)
- [Issue tracker](http://github.com/nrk/predis/issues)

### Related ###
- [Redis](http://redis.io/)
- [PHP](http://php.net/)
- [PHPUnit](http://www.phpunit.de/)
- [Git](http://git-scm.com/)

## Author ##

- [Daniele Alessandri](mailto:suppakilla@gmail.com) ([twitter](http://twitter.com/JoL1hAHN))

## Contributors ##

- [Lorenzo Castelli](http://github.com/lcastelli)
- [Jordi Boggiano](http://github.com/Seldaek) ([twitter](http://twitter.com/seldaek))
- [Sebastian Waisbrot](http://github.com/seppo0010) for his work on extending [phpiredis](http://github.com/seppo0010/phpiredis) for Predis

## License ##

The code for Predis is distributed under the terms of the MIT license (see LICENSE).
