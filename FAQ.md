# Some frequently asked questions about Predis #
____________________________________________


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


### How about performances? ###

Please refer to the dedicated __FAQ.PERFORMANCES__ file.


### Why PHP 5.3? ###

Seriously, are you still using PHP 5.2 to build new applications? I assume that if you are throwing Redis
in the mix, then you are probably coding something new after all. PHP 5.3 is faster, less memory hungry
and has a few nice features such as namespaces and closures (kind of). More importantly, PHP 5.2 is not
even officially supported anymore (aside from security patches). PHP 5.3 is not the future of PHP, but
its current present. Furthermore, most of the existing frameworks out there are also making the switch
with their respective new major versions. If you still insist on using PHP 5.2, you can get any recent
backported release of Predis 0.6.x, or just use a different library.


### Why so many files for just one library? ###

Before v0.7, Predis used the one-big-file approach to distribute the library. As much as you prefer having
just one file for everything, this kind of solution is actually not that good. Predis now complies with the
[PSR-0](http://groups.google.com/group/php-standards/web/psr-0-final-proposal) standard to play nice with
the major recent frameworks and libraries, so it needs an autoloader function to be defined. If you still
want to have just one file grouping all the classes for whatever reason, then the __bin/create-single-file.php__
script in the repository can generate it for you. There is also the __bin/create-phar.php__ script that
generates a single [Phar archive](http://www.php.net/manual/en/intro.phar.php) of the whole library.


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

```
$client = new Predis\Client();

try {
    $client->connect();
}
catch (Predis\Network\ConnectionException $exception) {
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

```
$client->rpush('my:list', 'value1', 'value2', 'value3');                 // values as arguments
$client->rpush('my:list', array('value1', 'value2', 'value3'));          // values as single argument array

$client->hmset('my:hash', 'field1', 'value1', 'field2', 'value2');       // values as arguments
$client->hmset('my:hash', array('field1'=>'value1', 'field2'=>'value2'); // values as single named array
```

The only exception to this _rule_ is the [SORT](http://redis.io/commands/sort) command for which modifiers are
[passed using a named array](https://github.com/nrk/predis/blob/v0.7.1/tests/Predis/Commands/KeySortTest.php#L56-77).
