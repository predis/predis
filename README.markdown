# Predis #

## About ##

Predis is a flexible and feature-complete PHP client library for the Redis key-value 
database.

Predis is currently a work-in-progress and it targets PHP >= 5.3, though it is highly 
due to be backported to PHP >= 5.2.6 as soon as the public API and the internal design 
on the main branch will be considered stable enough.

Please refer to the TODO file to see which issues are still pending and what is due 
to be implemented soon in Predis.


## Features ##

- Client-side sharding (support for consistent hashing of keys)
- Command pipelining on single and multiple connections (transparent)
- Lazy connections (connections to Redis instances are only established just in time)
- Flexible system to define and register your own set of commands to a client instance


## Quick examples ##

### Connecting to a local instance of Redis ###


    $redis = new Predis\Client();
    $redis->set('library', 'predis');
    $value = $redis->get('library');


### Pipelining multiple commands to a remote instance of Redis ##


    $redis   = new Predis\Client('10.0.0.1', 6379);
    $replies = $redis->pipeline(function($pipe) {
        $pipe->ping();
        $pipe->incrby('counter', 10);
        $pipe->incrby('counter', 30);
        $pipe->get('counter');
    });


### Pipelining multiple commands to multiple instances of Redis (sharding) ##


    $redis = Predis\Client::createCluster(
        array('host' => '10.0.0.1', 'port' => 6379),
        array('host' => '10.0.0.2', 'port' => 6379)
    );

    $replies = $redis->pipeline(function($pipe) {
        for ($i = 0; $i < 1000; $i++) {
            $pipe->set("key:$i", str_pad($i, 4, '0', 0));
            $pipe->get("key:$i");
        }
    });


### Definition and runtime registration of new commands on the client ###


class BrandNewRedisCommand extends \Predis\InlineCommand {
    public function getCommandId() { return 'NEWCMD'; }
}

$redis = new Predis\Client();
$redis->registerCommand('BrandNewRedisCommand', 'newcmd');
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

When modifying Predis plrease be sure that no warning or notices are emitted by PHP by 
running the interpreter in your development environment with the "error_reporting"
variable set to E_ALL.


## Dependencies ##

- PHP >= 5.3
- PHPUnit (needed to run the test suite)

## Links ##

### Project ###
[Source code](https://github.com/nrk/predis/)
[Issue tracker](http://github.com/nrk/predis/issues)

### Related ###
[Redis](http://code.google.com/p/redis/)
[PHP](http://php.net/)
[PHPUnit](http://www.phpunit.de/)
[Git](http://git-scm.com/)

## Author ##

[Daniele Alessandri](mailto://suppakilla@gmail.com)


## License ##

The code for Predis is distributed under the terms of the MIT license (see LICENSE).
