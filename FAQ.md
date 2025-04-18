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

If you care about performance, __always__. [Relay][relay] is free to use.

## When should I use PhpRedis? ###

Predis is fast enough when Redis is located on the same machine as PHP.

[PhpRedis][phpredis] and [Relay][relay] perform significantly better when network I/O is involved,
due to its ability to compress data by ~75%. Fewer bytes and received sent over the network
[means faster operations][performance], and potentially cost savings when network traffic isn't
free (e.g. AWS ElastiCache Inter-AZ transfer costs).

[phpredis]: https://github.com/phpredis/phpredis
[relay]: [https://github.com/phpredis/phpredis](https://github.com/cachewerk/relay)
[performance]: https://akalongman.medium.com/phpredis-vs-predis-comparison-on-real-production-data-a819b48cbadb
