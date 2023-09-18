# Frequently asked questions about Predis #

## What is the point of Predis? ##

The main point of Predis is about offering a highly customizable and extensible client for Redis,
that can be easily extended by developers while still being reasonably fast. With Predis you can
swap almost any class with your own custom implementation: you can have custom connection classes,
new distribution strategies for client-side sharding, or handlers to replace or add Redis commands.
All of this can be achieved without messing with the source code of the library and directly in your
own application. Given the fast pace at which Redis is developed and adds new features, this can be
a great asset since it allows developers to add new and still missing features or commands or change
the standard behaviour of the library without the need to break dependencies in production code (at
least to some degree).

## Does Predis support UNIX domain sockets and persistent connections? ##

Yes. Obviously persistent connections actually work only when using PHP configured as a persistent
process reused by the web server (see [PHP-FPM](http://php-fpm.org)).

## Does Predis support SSL-encrypted connections? ##

Yes. Encrypted connections are mostly useful when connecting to Redis instances exposed by various
cloud hosting providers without the need to configure an SSL proxy, but you should also take into
account the general performances degradation especially during the connect() operation when the TLS
handshake must be performed to secure the connection. Persistent SSL-encrypted connections may help
in that respect, but they are supported only when running on PHP >= 7.0.0.

## Does Predis support transparent (de)serialization of values? ##

When using [Relay](https://github.com/cachewerk/relay) as the underlying client, several
serialization and compression algorithms are supported. This slightly increases CPU usage,
but significantly reduces bytes sent over the network and Redis memory usage.

Without Relay, Predis will not serialize data and will never do that by default. The reason
behind this decision is that serialization is usually something that developers prefer to
customize depending on their needs and can not be easily generalized when using Redis because
of the many possible access patterns for your data. This does not mean that it is impossible
to have such a feature since you can leverage the extensibility of this library to define
your own serialization-aware commands. You can find more details about how to do that
[on this issue](http://github.com/predis/predis/issues/29#issuecomment-1202624).

## How can I force Predis to connect to Redis before sending any command? ##

Explicitly connecting to Redis is usually not needed since the client initializes connections lazily
only when they are needed. Admittedly, this behavior can be inconvenient in certain scenarios when
you absolutely need to perform an upfront check to determine if the server is up and running and
eventually catch exceptions on failures. Forcing the client to open the underlying connection can be
done by invoking `Predis\Client::connect()`:

```php
$client = new Predis\Client();

try {
    $client->connect();
} catch (Predis\Connection\ConnectionException $exception) {
    // We could not connect to Redis! Your handling code goes here.
}

$client->info();
```

## How Predis abstracts Redis commands? ##

The approach used to implement Redis commands is quite simple: by default each command follows the
same signature as defined on the [Redis documentation](http://redis.io/commands) which makes things
pretty easy if you already know how Redis works or you need to look up how to use certain commands.
Alternatively, variadic commands can accept an array for keys or values (depending on the command)
instead of a list of arguments. Commands such as [`RPUSH`](http://redis.io/commands/rpush) and
[`HMSET`](http://redis.io/commands/hmset) are great examples:

```php
$client->rpush('my:list', 'value1', 'value2', 'value3');             // plain method arguments
$client->rpush('my:list', ['value1', 'value2', 'value3']);           // single argument array

$client->hmset('my:hash', 'field1', 'value1', 'field2', 'value2');   // plain method arguments
$client->hmset('my:hash', ['field1'=>'value1', 'field2'=>'value2']); // single named array
```

An exception to this rule is [`SORT`](http://redis.io/commands/sort) for which modifiers are passed
[using a named array](tests/Predis/Command/KeySortTest.php#L54-L75).

## When should I use Relay? ##

If you care about performance, __always__. [Relay](https://github.com/cachewerk/relay) is free to use.

## When should I use PhpRedis? ###

Predis is fast enough when Redis is located on the same machine as PHP, more on that later.

[PhpRedis](https://github.com/phpredis/phpredis) (and Relay) perform significantly better when
network I/O is involved, due to their ability to compress data by ~75%. Fewer bytes and received
sent over the network [means faster operations](https://akalongman.medium.com/phpredis-vs-predis-comparison-on-real-production-data-a819b48cbadb),
and potentially cost savings when network traffic isn't free (e.g. AWS ElastiCache Inter-AZ transfer costs).

## Predis is a pure-PHP implementation: it can not be fast enough! ##

It really depends, but most of the times the answer is: _yes, it is fast enough_. I will give you a
couple of easy numbers with a simple test that uses a single client and is executed by PHP 5.5.6
against a local instance of Redis 2.8 that runs under Ubuntu 13.10 on a Intel Q6600:

```
21000 SET/sec using 12 bytes for both key and value.
21000 GET/sec while retrieving the very same values.
0.130 seconds to fetch 30000 keys using _KEYS *_.
```

How does it compare with [__PhpRedis__](http://github.com/phpredis/phpredis), a nice C extension
providing an efficient client for Redis?

```
30100 SET/sec using 12 bytes for both key and value
29400 GET/sec while retrieving the very same values
0.035 seconds to fetch 30000 keys using "KEYS *"".
```

Wow __PhpRedis__ seems much faster! Well, we are comparing a C extension with a pure-PHP library so
lower numbers are quite expected but there is a fundamental flaw in them: is this really how you are
going to use Redis in your application? Are you really going to send thousands of commands using a
for-loop on each page request using a single client instance? If so... well I guess you are probably
doing something wrong. Also, if you need to `SET` or `GET` multiple keys you should definitely use
commands such as `MSET` and `MGET`. You can also use pipelining to get more performances when this
technique can be used.

There is one more thing: we have tested the overhead of Predis by connecting on a localhost instance
of Redis but how these numbers change when we hit the physical network by connecting to remote Redis
instances?

```
Using Predis:
3200 SET/sec using 12 bytes for both key and value
3200 GET/sec while retrieving the very same values
0.132 seconds to fetch 30000 keys using "KEYS *".

Using PhpRedis:
3500 SET/sec using 12 bytes for both key and value
3500 GET/sec while retrieving the very same values
0.045 seconds to fetch 30000 keys using "KEYS *".
```

There you go, you get almost the same average numbers and the reason is simple: network latency is a
real performance killer and you cannot do (almost) anything about that. As a disclaimer, remember
that we are measuring the overhead of client libraries implementations and the effects of network
round-trip times, so we are not really measuring how fast Redis is. Redis shines best with thousands
of concurrent clients doing requests! Also, actual performances should be measured according to how
your application will use Redis.
