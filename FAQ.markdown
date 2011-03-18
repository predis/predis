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


### Nice, but it is still a pure-PHP implementation: it can't be fast enough! ###

It really depends, but most of the times the answer is: _yes, it is fast enough_. I will give you
a couple of easy numbers using a single Predis client with PHP 5.3.5 (custom build) and Redis 2.2
(localhost) under Ubuntu 10.10 (running on a Intel Q6600):

    18500 SET/sec using 12 bytes for both key and value
    18100 GET/sec while retrieving the very same values
    0.210 seconds to fetch 30000 keys using _KEYS *_.

How does it compare with a nice C-based extension such as [__phpredis__](http://github.com/nicolasff/phpredis)?

    29000 SET/sec using 12 bytes for both key and value
    30000 GET/sec while retrieving the very same values
    0.037 seconds to fetch 30000 keys using "KEYS *"".

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
    3100 SET/sec using 12 bytes for both key and value
    3100 GET/sec while retrieving the very same values
    0.212 seconds to fetch 30000 keys using "KEYS *".

    Using phpredis:
    3300 SET/sec using 12 bytes for both key and value
    3300 GET/sec while retrieving the very same values
    0.088 seconds to fetch 30000 keys using "KEYS *".

There you go, you get almost the same average numbers and the reason is quite simple: network latency
is a real performance killer and you cannot do (almost) anything about that. As a disclaimer, please
remember that we are measuring the overhead of client libraries implementations and the effects of the
network round-trip time, we are not really measuring how fast Redis is. Redis shines the best with
thousands of concurrent clients doing requests! Also, actual performances should be measured according
to how your application will use Redis.


### I am convinced, but performances for multi-bulk replies (e.g. _KEYS *_) are still worse ###

Fair enough, but there is actually an option for you if you need even more speed and it consists on
installing __[phpiredis](http://github.com/seppo0010/phpiredis)__ (note the additional _i_ in the
name) and let Predis using it. __phpiredis__ is a C-based extension that wraps __hiredis__ (the
official Redis C client library) with a thin layer that exposes its features to PHP. You will now
get the benefits of a faster protocol parser just by adding a single line of code in your application:

    Predis\Client::defineConnection('tcp', '\Predis\Network\PhpiredisConnection');

As simple as it is, nothing will really change in the way you use the library in your application. So,
how fast is it now? There are not much improvements for inline or short bulk replies (e.g. _SET_ or
_GET_), but the speed for parsing multi-bulk replies is now on par with phpredis:

    Using Predis with a phpiredis-based connection to fetch 30000 keys using _KEYS *_:

    0.037 seconds from a local Redis instance
    0.081 seconds from a remote Redis instance


### If I need to install a C extension to get better performances, why not using phpredis? ###

Good question. Generically speaking, if you need absolute uber-speed using localhost instances of Redis
and you do not care about abstractions built around some Redis features such as MULTI / EXEC, or if you
do not need any kind of extensibility or guaranteed backwards compatibility with different versions of
Redis (Predis currently supports from 1.2 up to 2.2, and even the current development version), then
using __phpredis__ can make sense for you. Otherwise, Predis is for you. Using __phpiredis__ gives you
a nice speed bump, but it is not mandatory.


### Why PHP 5.3? ###

Seriously, are you still using PHP 5.2 to build new applications? I assume that if you are throwing Redis
in the mix, then you are probably coding something new after all. PHP 5.3 is faster, less memory hungry
and has a few nice features such as namespaces and closures (kind of). More importantly, PHP 5.2 is not
even officially supported anymore (aside from security patches). PHP 5.3 is not the future of PHP, but
its current present. Furthermore, most of the existing frameworks out there are also making the switch
with their respective new major versions. If you still insist on using PHP 5.2, you can get any recent
backported release of Predis 0.6.x, or just use a different library.
