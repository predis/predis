# Some frequently asked questions about Predis #
_________________________________________________


### What is the point of Predis? ###

The main point of Predis is about offering a highly customizable client for Redis that can be easily
extended by developers while still being reasonabily fast. With Predis you can swap almost any class
used internally with your own custom implementation: you can build connection classes, or new
distribution strategies for client-side sharding, or class handlers to replace existing commands or
add new ones. All of this can be achieved without messing with the source code of the library and
directly in your own application. Given the fast pace at which Redis is developed and adds new
features, this can be a great asset that allows you to add new and still missing features or commands,
or change the behaviour of the library without the need to break your dependencies in production code
(well, at least to some degree).

### Does Predis support UNIX domain sockets and persistent connections? ###

Yes. Obviously, persistent connections actually work when using PHP configured as a persistent process that
gets recycled between requests (see [PHP-FPM](http://php-fpm.org/)).


### Does Predis support transparent (de)serialization of values? ###

No, and it will not ever do that for you by default. The reason behind this decision is that serialization
is usually something that developers prefer to customize depending on their needs and can not be easily
generalized when using Redis because of the many possible access patterns for the data. This does not
mean that it is impossible to have such a feature, you can leverage Predis' extensibility to define your
own serialization-aware commands. See [here](http://github.com/nrk/predis/issues/29#issuecomment-1202624)
for more details on how to implement such a feature with a practical example.


### How can I force Predis to connect to Redis before sending any command? ###

Explicitly connecting to Redis is usually not needed since the client library relies on lazily initialized
connections to the server, but this behavior can be inconvenient in certain scenarios when you absolutely
need to do an upfront check to detect if the server is up and running and eventually catch exceptions on
failures. In this case developers can use `Predis\Client::connect()` to explicitly connect to the server:

```php
$client = new Predis\Client();

try {
    $client->connect();
} catch (Predis\Connection\ConnectionException $exception) {
    // We could not connect to Redis! Your handling code goes here.
}

$client->info();
```


### How Predis implements abstraction of Redis commands? ###

The approach used in Predis to implement the abstraction of Redis commands is quite simple. By default
every command in the library follows exactly the same argument list as defined in the great online
[Redis documentation](http://redis.io/commands) which makes things pretty easy if you already know how
Redis works or if you need to look up how to use certain commands. Alternatively, variadic commands can
accept an array for keys or values (depending on the command) instead of a list of arguments. See for
example how [RPUSH](http://redis.io/commands/rpush) or [HMSET](http://redis.io/commands/hmset) work:

```php
$client->rpush('my:list', 'value1', 'value2', 'value3');                 // values as arguments
$client->rpush('my:list', array('value1', 'value2', 'value3'));          // values as single argument array

$client->hmset('my:hash', 'field1', 'value1', 'field2', 'value2');       // values as arguments
$client->hmset('my:hash', array('field1'=>'value1', 'field2'=>'value2'); // values as single named array
```

The only exception to this _rule_ is the [SORT](http://redis.io/commands/sort) command for which modifiers are
[passed using a named array](tests/Predis/Command/KeySortTest.php#L56-77).



# Frequently asked questions about performances #
_________________________________________________


### Predis is a pure-PHP implementation: it can not be fast enough! ###

It really depends, but most of the times the answer is: _yes, it is fast enough_. I will give you
a couple of easy numbers using a single Predis client with PHP 5.4.7 (custom build) and Redis 2.2
(localhost) under Ubuntu 12.04.1 (running on a Intel Q6600):

    21500 SET/sec using 12 bytes for both key and value
    21000 GET/sec while retrieving the very same values
    0.130 seconds to fetch 30000 keys using _KEYS *_.

How does it compare with a nice C-based extension such as [__phpredis__](http://github.com/nicolasff/phpredis)?

    30100 SET/sec using 12 bytes for both key and value
    29400 GET/sec while retrieving the very same values
    0.035 seconds to fetch 30000 keys using "KEYS *"".

Wow, __phpredis__ looks so much faster! Well we are comparing a C extension with a pure-PHP library so
lower numbers are quite expected, but there is a fundamental flaw in them: is this really how you are
going to use Redis in your application? Are you really going to send thousands of commands in a for-loop
for each page request using a single client instance? If so, well I guess you are probably doing something
wrong. Also, if you need to SET or GET multiple keys you should definitely use commands such as MSET and
MGET. You can also use pipelining to get more performances when this technique can be used.

There is one more thing. We have tested the overhead of Predis by connecting on a localhost instance of
Redis, but how these numbers change when we hit the network by connecting to instances of Redis that
reside on other servers?

    Using Predis:
    3200 SET/sec using 12 bytes for both key and value
    3200 GET/sec while retrieving the very same values
    0.132 seconds to fetch 30000 keys using "KEYS *".

    Using phpredis:
    3500 SET/sec using 12 bytes for both key and value
    3500 GET/sec while retrieving the very same values
    0.045 seconds to fetch 30000 keys using "KEYS *".

There you go, you get almost the same average numbers and the reason is quite simple: network latency
is a real performance killer and you cannot do (almost) anything about that. As a disclaimer, please
remember that we are measuring the overhead of client libraries implementations and the effects of the
network round-trip time, we are not really measuring how fast Redis is. Redis shines the best with
thousands of concurrent clients doing requests! Also, actual performances should be measured according
to how your application will use Redis.


### I am convinced, but performances for multi-bulk replies (e.g. _KEYS *_) are still worse ###

Fair enough, but there is actually an option for you if you need even more speed and it consists on
installing __[phpiredis](http://github.com/nrk/phpiredis)__ (note the additional _i_ in the name)
and let Predis using it. __phpiredis__ is a C-based extension that wraps __hiredis__ (the official
Redis C client library) with a thin layer that exposes its features to PHP. You can choose between
two different connection backend classes: `Predis\Connection\PhpiredisConnection` (it depends on the
`socket` extension) and `Predis\Connection\PhpiredisStreamConnection` (it uses PHP's native streams).
You will now get the benefits of a faster protocol parser just by adding a couple of lines of code:

```php
$client = new Predis\Client('tcp://127.0.0.1', array(
    'connections' => array(
    	'tcp'  => 'Predis\Connection\PhpiredisConnection',
    	'unix' => 'Predis\Connection\PhpiredisConnection',
	),
));
```

As simple as it is, nothing will really change in the way you use the library in your application. So,
how fast is it now? There are not much improvements for inline or short bulk replies (e.g. _SET_ or
_GET_), but the speed for parsing multi-bulk replies is now on par with phpredis:

    Using Predis with a phpiredis-based connection to fetch 30000 keys using _KEYS *_:

    0.035 seconds from a local Redis instance
    0.047 seconds from a remote Redis instance


### If I need to install a C extension to get better performances, why not using phpredis? ###

Good question. Generically speaking, if you need absolute uber-speed using localhost instances of Redis
and you do not care about abstractions built around some Redis features such as MULTI / EXEC, or if you
do not need any kind of extensibility or guaranteed backwards compatibility with different versions of
Redis (Predis currently supports from 1.2 up to 2.6, and even the current development version), then
using __phpredis__ can make sense for you. Otherwise, Predis is perfect for the job. __phpiredis__
can give you a nice speed bump, but using it is not mandatory.
