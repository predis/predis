# Predis #
[![Latest Stable Version](https://poser.pugx.org/predis/predis/v/stable.png)](https://packagist.org/packages/predis/predis)
[![Total Downloads](https://poser.pugx.org/predis/predis/downloads.png)](https://packagist.org/packages/predis/predis)

Predis is a flexible and feature-complete PHP (>= 5.3) client library for the Redis key-value store.

The library does not require any additional extension loaded in PHP but it can be optionally paired
with the [phpiredis](https://github.com/nrk/phpiredis) C-based extension to lower the overhead of
serializing and parsing the Redis protocol. Predis is also available in an asynchronous fashion
through the experimental client provided by the [Predis\Async](http://github.com/nrk/predis-async)
library.

For a list of frequently asked questions about Predis see our [FAQ](FAQ.md).
More details are available on the [official wiki](http://wiki.github.com/nrk/predis) of the project.


## Main features ##

- Wide range of Redis versions supported (from __1.2__ to __2.6__ and unstable) using server profiles.
- Smart support for [redis-cluster](http://redis.io/topics/cluster-spec) (Redis >= 3.0).
- Client-side sharding via consistent hashing or custom distribution strategies.
- Support for master / slave replication configurations (write on master, read from slaves).
- Transparent key prefixing strategy capable of handling any command known that has keys in its arguments.
- Command pipelining on single and aggregated connections.
- Abstraction for Redis transactions (Redis >= 2.0) with support for CAS operations (Redis >= 2.2).
- Abstraction for Lua scripting (Redis >= 2.6) capable of automatically switching between `EVAL` and `EVALSHA`.
- Connections to Redis instances are lazily established upon the first call to a command by the client.
- Ability to connect to Redis using TCP/IP or UNIX domain sockets with support for persistent connections.
- Ability to specify alternative connection classes to use different types of network or protocol backends.
- Flexible system to define and register your own set of commands or server profiles to client instances.


## How to use Predis ##

Predis is available on [Packagist](http://packagist.org/packages/predis/predis) for an easy installation
using [Composer](http://packagist.org/about-composer). Composer helps you manage dependencies for your
projects and libraries without much hassle which makes it the preferred way to get up and running with
new applications. Alternatively, the library is available on our [own PEAR channel](http://pear.nrk.io)
for a more traditional installation via PEAR. Zip and tar.gz archives are also downloadable from GitHub
by browsing the list of [tagged releases](http://github.com/nrk/predis/tags).


### Loading the library ###

Predis relies on the autoloading features of PHP to load its files when needed and complies with the
[PSR-0 standard](http://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) which makes it
compatible with most of the major frameworks and libraries. Autoloading in your application is handled
automatically when managing the dependencies with Composer, but you can also leverage its own autoloader
class if you are going to use it in a project or script without any PSR-0 compliant autoloading facility:

```php
<?php
// prepend a base path if Predis is not present in your "include_path".
require 'Predis/Autoloader.php';

Predis\Autoloader::register();
```

It is possible to create a single [Phar](http://www.php.net/manual/en/intro.phar.php) archive from the
repository just by launching `bin/create-phar.php`. The generated Phar archive ships with a stub defining
an autoloader function for Predis, so you just need to require the Phar to be able to use the library.

Alternatively it is possible to generate a single PHP file that holds every class, just like older versions
of Predis, using `bin/create-single-file.php`. In this way you can load Predis in your scripts simply by
using functions such as `require` and `include`, but this practice is not encouraged.


### Connecting to Redis ###

By default Predis uses `127.0.0.1` and `6379` as the default host and port when creating a new client
instance without specifying any connection parameter:

```php
$redis = new Predis\Client();
$redis->set('foo', 'bar');
$value = $redis->get('foo');
```

It is possible to specify the various connection parameters using URI strings or named arrays:

```php
$redis = new Predis\Client('tcp://10.0.0.1:6379');

// is equivalent to:

$redis = new Predis\Client(array(
    'scheme' => 'tcp',
    'host'   => '10.0.0.1',
    'port'   => 6379,
));
```


### Pipelining commands to multiple instances of Redis with client-side sharding ###

Pipelining helps with performances when there is the need to send many commands to a server in one go.
Furthermore, pipelining works transparently even on aggregated connections. To achieve this, Predis
supports client-side sharding using consistent-hashing on keys while clustered connections are supported
natively by the client class.

```php
$redis = new Predis\Client(array(
    array('host' => '10.0.0.1', 'port' => 6379),
    array('host' => '10.0.0.2', 'port' => 6379)
));

$replies = $redis->pipeline(function ($pipe) {
    for ($i = 0; $i < 1000; $i++) {
        $pipe->set("key:$i", str_pad($i, 4, '0', 0));
        $pipe->get("key:$i");
    }
});
```


### Multiple and customizable connection backends ###

Predis can optionally use different connection backends to connect to Redis. Two of them leverage
the [phpiredis](http://github.com/nrk/phpiredis) C-based extension resulting in a major speed bump
especially when dealing with long multibulk replies, namely `Predis\Connection\PhpiredisConnection`
(the `socket` extension is also required) and `Predis\Connection\StreamPhpiredisConnection` (it
does not require additional extensions since it relies on PHP's native streams). Both of them can
connect to Redis using standard TCP/IP connections or UNIX domain sockets:

```php
$client = new Predis\Client('tcp://127.0.0.1', array(
    'connections' => array(
        'tcp'  => 'Predis\Connection\PhpiredisConnection',
        'unix' => 'Predis\Connection\PhpiredisStreamConnection',
    )
));
```

Developers can also create their own connection backends to add support for new protocols, extend
existing ones or provide different implementations. Connection backend classes must implement
`Predis\Connection\SingleConnectionInterface` or extend `Predis\Connection\AbstractConnection`:

```php
class MyConnectionClass implements Predis\Connection\SingleConnectionInterface
{
    // implementation goes here
}

// Let Predis automatically use your own class to handle connections identified by the tcp scheme.
$client = new Predis\Client('tcp://127.0.0.1', array(
    'connections' => array('tcp' => 'MyConnectionClass')
));
```

For a more in-depth insight on how to create new connection backends you can look at the actual
implementation of the classes contained in the `Predis\Connection` namespace.


### Defining and registering new commands on the client at runtime ###

Let's suppose Redis just added the support for a brand new feature associated with a new command. If
you want to start using the above mentioned new feature right away without messing with Predis source
code or waiting for it to find its way into a stable Predis release, then you can start off by creating
a new class that matches the command type and its behaviour and then bind it to a client instance at
runtime. Actually, it is easier done than said:

```php
class BrandNewRedisCommand extends Predis\Command\AbstractCommand
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


### Abstraction for handling Lua scripts as plain Redis commands ###

A scripted command in Predis is an abstraction for [Lua scripting](http://redis.io/commands/eval)
with Redis >= 2.6 that allows to use a Lua script as if it was a plain Redis command registered
in the server profile being used by the client instance. Internally, scripted commands use
[EVALSHA](http://redis.io/commands/evalsha) to refer to a Lua script by its SHA1 hash in order
to save bandwidth, but they are capable of falling back to [EVAL](http://redis.io/commands/eval)
when needed:

```php
class ListPushRandomValue extends Predis\Command\ScriptedCommand
{
    public function getKeysCount()
    {
        return 1;
    }

    public function getScript()
    {
        return
<<<LUA
math.randomseed(ARGV[1])
local rnd = tostring(math.random())
redis.call('lpush', KEYS[1], rnd)
return rnd
LUA;
    }
}

$client = new Predis\Client();
$client->getProfile()->defineCommand('lpushrand', 'ListPushRandomValue');

$value = $client->lpushrand('random_values', $seed = mt_rand());
```


## Test suite ##

__ATTENTION__: Do not ever run the test suite shipped with Predis against instances of Redis running in
production environments or containing data you are interested in!

Predis has a comprehensive test suite covering every aspect of the library. The suite performs integration
tests against a running instance of Redis (>= 2.4.0 is required) to verify the correct behaviour of the
implementation of each command and automatically skips commands not defined in the selected version of
Redis. If you do not have Redis up and running, integration tests can be disabled. By default, the test
suite is configured to execute integration tests using the server profile for Redis v2.4 (which is the
current stable version of Redis). You can optionally run the suite against a Redis instance built from
the `unstable` branch with the development profile by changing the `REDIS_SERVER_VERSION` to `dev` in
the `phpunit.xml` file. More details on testing Predis can be found in [the tests README](tests/README.md).

Predis uses Travis CI for continuous integration. You can find the results of the test suite and the build
history [on its project page](http://travis-ci.org/nrk/predis).


## Dependencies ##

- PHP >= 5.3.2
- PHPUnit >= 3.5.0 (needed to run the test suite)

## Links ##

### Project ###
- [Source code](http://github.com/nrk/predis/)
- [Wiki](http://wiki.github.com/nrk/predis/)
- [Issue tracker](http://github.com/nrk/predis/issues)
- [PEAR channel](http://pear.nrk.io)

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
- [Sebastian Waisbrot](http://github.com/seppo0010) ([twitter](http://twitter.com/seppo0010))
  for his past work on extending [phpiredis](http://github.com/nrk/phpiredis) for Predis.

## License ##

The code for Predis is distributed under the terms of the MIT license (see [LICENSE](LICENSE)).
