# Predis #

## About ##

Predis is a flexible and feature-complete PHP client library for the Redis key-value 
database. It currently comes in two flavors:

 - the mainline client library, which targets PHP 5.3.x and leverages a lot of the 
   features introduced in this new version of the PHP interpreter.
 - a backport to PHP 5.2.x for those who can not upgrade their environment yet 
   (it admittedly has a lower priority compared to the mainline library, although we 
   try to keep the two versions aligned as much as possible).

Please refer to the TODO file to see which issues are still pending and what is due 
to be implemented soon in Predis.


## Main features ##

- Full support for Redis 2.0 and 2.2. Different versions of Redis are supported via server profiles.
- Client-side sharding (support for consistent hashing and custom distribution strategies).
- Command pipelining on single and multiple connections (transparent).
- Abstraction for Redis transactions (>= 2.0) with support for CAS operations (>= 2.2).
- Lazy connections (connections to Redis instances are only established just in time).
- Ability to connect to Redis using TCP/IP or UNIX domain sockets.
- Flexible system to define and register your own set of commands to a client instance.


## Quick examples ##

See the [official wiki](http://wiki.github.com/nrk/predis) of the project for a more 
complete coverage of all the features available in Predis.

### Connecting to a local instance of Redis ###

You don't have to specify a tcp host and port when connecting to Redis instances 
running on the localhost on the default port:

    $redis = new Predis_Client();
    $redis->set('library', 'predis');
    $value = $redis->get('library');


### Pipelining multiple commands to a remote instance of Redis ##

Pipelining helps with performances when there is the need to issue many commands 
to a server in one go:

    $redis = new Predis_Client('redis://10.0.0.1:6379/');
    $pipe  = $redis->pipeline();
    $pipe->ping();
    $pipe->incrby('counter', 10);
    $pipe->incrby('counter', 30);
    $pipe->get('counter');
    $replies = $pipe->execute();


### Pipelining multiple commands to multiple instances of Redis (sharding) ##

Predis supports data sharding using consistent-hashing on keys on the client side. 
Furthermore, a pipeline can be initialized on a cluster of redis instances in the 
same exact way they are created on single connection. Sharding is still transparent 
to the user:

    $redis = new Predis_Client(array(
        array('host' => '10.0.0.1', 'port' => 6379),
        array('host' => '10.0.0.2', 'port' => 6379)
    ));

    $pipe = $redis->pipeline();
    for ($i = 0; $i < 1000; $i++) {
        $pipe->set("key:$i", str_pad($i, 4, '0', 0));
        $pipe->get("key:$i");
    }
    $replies = $pipe->flushPipeline();


### Definition and runtime registration of new commands on the client ###

Let's suppose Redis just added the support for a brand new feature associated 
with a new command. If you want to start using the above mentioned new feature 
right away without messing with Predis source code or waiting for it to find 
its way into a stable Predis release, then you can start off by creating a new 
class that matches the command type and its behaviour and then bind it to a 
client instance at runtime. Actually, it is easier done than said:

    class BrandNewRedisCommand extends Predis_MultiBulkCommand {
        public function getCommandId() { return 'NEWCMD'; }
    }

    $redis = new Predis_Client();
    $redis->getProfile()->registerCommand('BrandNewRedisCommand', 'newcmd');
    $redis->newcmd();


## Development ##

Predis is fully backed up by a test suite which tries to cover all the aspects of the 
client library and the interaction of every single command with a Redis server. If you 
want to work on Predis, it is highly recommended that you first run the test suite to 
be sure that everything is OK, and report strange behaviours or bugs.

The recommended way to contribute to Predis is to fork the project on GitHub, fix or 
add features on your newly created repository and then submit issues on the Predis 
issue tracker with a link to your repository. Obviously, you can use any other Git 
hosting provider of you preference. Diff patches will be accepted too, even though 
they are not the preferred way to contribute to Predis.

When modifying Predis please be sure that no warnings or notices are emitted by PHP 
by running the interpreter in your development environment with the "error_reporting"
variable set to E_ALL | E_STRICT.


## Dependencies ##

- PHP >= 5.3.0 (for the mainline client library)
- PHP >= 5.2.6 (for the backported client library)
- PHPUnit (needed to run the test suite)

## Links ##

### Project ###
- [Source code](http://github.com/nrk/predis/)
- [Wiki](http://wiki.github.com/nrk/predis/)
- [Issue tracker](http://github.com/nrk/predis/issues)

### Related ###
- [Redis](http://code.google.com/p/redis/)
- [PHP](http://php.net/)
- [PHPUnit](http://www.phpunit.de/)
- [Git](http://git-scm.com/)

## Author ##

[Daniele Alessandri](mailto:suppakilla@gmail.com)

## Contributors ##

[Lorenzo Castelli](http://github.com/lcastelli)

## License ##

The code for Predis is distributed under the terms of the MIT license (see LICENSE).
