v0.7.1 (201x-xx-xx)
===============================================================================

- Miscellaneous minor fixes.

- Added transparent support for master / slave replication configurations where
  write operations are performed on the master server and read operations are
  routed to one of the slaves. Please refer to ISSUE #21 for a bit of history
  and more details about replication support in Predis.

- The 'profile' client option now accepts a callable object used to initialize
  a new instance of Predis\Profiles\IServerProfile.

- PearHub's PEAR channel has been deprecated in favour of `pear.nrk.io`.


v0.7.0 (2011-12-11)
===============================================================================

- Predis now adheres to the PSR-0 standard which means that there is no more a
  single file holding all the classes of the library, but multiple files (one
  for each class). You can use any PSR-0 compatible autoloader to load Predis
  or just leverage the default one shipped with the library by requiring the
  `Predis/Autoloader.php` and call `Predis\Autoloader::register()`.

- The default server profile for Redis is now 2.4. The `dev` profile supports
  all the features of Redis 2.6 (currently unstable) such as Lua scripting.

- Support for long aliases (method names) for Redis commands has been dropped.

- Redis 1.0 is no more supported. From now on Predis will use only the unified
  protocol to serialize commands.

- It is possible to prefix keys transparently on a client-level basis with the
  new `prefix` client option.

- An external connection factory is used to initialize new connection instances
  and developers can now register their own connection classes using the new
  `connections` client option.

- It is possible to connect locally to Redis using UNIX domain sockets. Just
  use `unix:///path/to/redis.sock` or a named array just like in the following
  example: `array('scheme' => 'unix', 'path' => '/path/to/redis.sock');`.

- If the `phpiredis` extension is loaded by PHP, it is now possible to use an
  alternative connection class that leverages it to make Predis faster on many
  cases, especially when dealing with big multibulk replies, with the the only
  downside that persistent connections are not supported. Please refer to the
  documentation to see how to activate this class using the new `connections`
  client option.

- Predis is capable to talk with Webdis, albeit with some limitations such as
  the lack of pipelining and transactions, just by using the `http` scheme in
  in the connection parameters. All is needed is PHP with the `curl` and the
  `phpiredis` extensions loaded.

- Way too many changes in the public API to make a list here, we just tried to
  make all the Redis commands compatible with previous releases of v0.6 so that
  you do not have to worry if you are simply using Predis as a client. Probably
  the only breaking changes that should be mentioned here are:

  - `throw_on_error` has been renamed to `throw_errors` and it is a connection
    parameter instead of a client option, along with `iterable_multibulk`.

  - `key_distribution` has been removed from the client options. To customize
    the distribution strategy you must provide a callable object to the new
    `cluster` client option to configure and then return a new instance of
    `Predis\Network\IConnectionCluster`.

  - `Predis\Client::create()` has been removed. Just use the constructor to set
    up a new instance of `Predis\Client`.

  - `Predis\Client::pipelineSafe()` was deprecated in Predis v0.6.1 and now has
    finally removed. Use `Predis\Client::pipeline(array('safe' => true))`.

  - `Predis\Client::rawCommand()` has been removed due to inconsistencies with
    the underlying connection abstractions. You can still get the raw resource
    out of a connection with `Predis\Network\IConnectionSingle::getResource()`
    so that you can talk directly with Redis.

- The `Predis\MultiBulkCommand` class has been merged into `Predis\Command` and
  thus removed. Serialization of commands is now a competence of connections.

- The `Predis\IConnection` interface has been splitted into two new interfaces:
  `Predis\Network\IConnectionSingle` and `Predis\Network\IConnectionCluster`.

- The constructor of `Predis\Client` now accepts more type of arguments such as
  instances of `Predis\IConnectionParameters` and `Predis\Network\IConnection`.


v0.6.6 (2011-04-01)
===============================================================================

- Switched to Redis 2.2 as the default server profile (there are no changes
  that would break compatibility with previous releases). Long command names
  are no more supported by default but if you need them you can still require
  Predis_Compatibility.php to avoid breaking compatibility.

- Added a `VERSION` constant to `Predis\Client`.

- Some performance improvements for multibulk replies (parsing them is about
  16% faster than the previous version). A few core classes have been heavily
  optimized to reduce overhead when creating new instances.

- Predis now uses by default a new protocol reader, more lightweight and
  faster than the default handler-based one. Users can revert to the old
  protocol reader with the 'reader' client option set to `composable`.
  This client option can also accept custom reader classes implementing the
  new `Predis\IResponseReader` interface.

- Added support for connecting to Redis using UNIX domain sockets (ISSUE #25).

- The `read_write_timeout` connection parameter can now be set to 0 or false
  to disable read and write timeouts on connections. The old behaviour of -1
  is still intact.

- `ZUNIONSTORE` and `ZINTERSTORE` can accept an array to specify a list of the
  source keys to be used to populate the destination key.

- `MGET`, `SINTER`, `SUNION` and `SDIFF` can accept an array to specify a list
  of keys. `SINTERSTORE`, `SUNIONSTORE` and `SDIFFSTORE` can also accept an
  array to specify the list of source keys.

- `SUBSCRIBE` and `PSUBSCRIBE` can accept a list of channels for subscription.

- __FIX__: some client-side clean-ups for `MULTI/EXEC` were handled incorrectly
  in a couple of corner cases (ISSUE #27).


v0.6.5 (2011-02-12)
===============================================================================

- __FIX__: due to an untested internal change introduced in v0.6.4, a wrong
  handling of bulk reads of zero-length values was producing protocol
  desynchronization errors (ISSUE #20).


v0.6.4 (2011-02-12)
===============================================================================

- Various performance improvements (15% ~ 25%) especially when dealing with
  long multibulk replies or when using clustered connections.

- Added the `on_retry` option to `Predis\MultiExecBlock` that can be used to
  specify an external callback (or any callable object) that gets invoked
  whenever a transaction is aborted by the server.

- Added inline (p)subscribtion via options when initializing an instance of
  `Predis\PubSubContext`.


v0.6.3 (2011-01-01)
===============================================================================

- New commands available in the Redis v2.2 profile (dev):
  - Strings: `SETRANGE`, `GETRANGE`, `SETBIT`, `GETBIT`
  - Lists  : `BRPOPLPUSH`

- The abstraction for `MULTI/EXEC` transactions has been dramatically improved
  by providing support for check-and-set (CAS) operations when using Redis >=
  2.2. Aborted transactions can also be optionally replayed in automatic up
  to a user-defined number of times, after which a `Predis\AbortedMultiExec`
  exception is thrown.


v0.6.2 (2010-11-28)
===============================================================================

- Minor internal improvements and clean ups.

- New commands available in the Redis v2.2 profile (dev):
  - Strings: `STRLEN`
  - Lists  : `LINSERT`, `RPUSHX`, `LPUSHX`
  - ZSets  : `ZREVRANGEBYSCORE`
  - Misc.  : `PERSIST`

- WATCH also accepts a single array parameter with the keys that should be
  monitored during a transaction.

- Improved the behaviour of `Predis\MultiExecBlock` in certain corner cases.

- Improved parameters checking for the SORT command.

- __FIX__: the `STORE` parameter for the `SORT` command didn't work correctly
  when using `0` as the target key (ISSUE #13).

- __FIX__: the methods for `UNWATCH` and `DISCARD` do not break anymore method
  chaining with `Predis\MultiExecBlock`.


v0.6.1 (2010-07-11)
===============================================================================

- Minor internal improvements and clean ups.

- New commands available in the Redis v2.2 profile (dev):
  - Misc.  : `WATCH`, `UNWATCH`

- Optional modifiers for `ZRANGE`, `ZREVRANGE` and `ZRANGEBYSCORE` queries are
  supported using an associative array passed as the last argument of their
  respective methods.

- The `LIMIT` modifier for `ZRANGEBYSCORE` can be specified using either:
  - an indexed array: `array($offset, $count)`
  - an associative array: `array('offset' => $offset, 'count' => $count)`

- The method `Predis\Client::__construct()` now accepts also instances of
  `Predis\ConnectionParameters`.

- `Predis\MultiExecBlock` and `Predis\PubSubContext` now throw an exception
  when trying to create their instances using a profile that does not
  support the required Redis commands or when the client is connected to
  a cluster of connections.

- Various improvements to `Predis\MultiExecBlock`:
  - fixes and more consistent behaviour across various usage cases.
  - support for `WATCH` and `UNWATCH` when using the current development
    profile (Redis v2.2) and aborted transactions.

- New signature for `Predis\Client::multiExec()` which is now able to accept
  an array of options for the underlying instance of `Predis\MultiExecBlock`.
  Backwards compatibility with previous releases of Predis is ensured.

- New signature for `Predis\Client::pipeline()` which is now able to accept
  an array of options for the underlying instance of Predis\CommandPipeline.
  Backwards compatibility with previous releases of Predis is ensured.
  The method `Predis\Client::pipelineSafe()` is to be considered deprecated.

- __FIX__: The `WEIGHT` modifier for `ZUNIONSTORE` and `ZINTERSTORE` was
  handled incorrectly with more than two weights specified.


v0.6.0 (2010-05-24)
===============================================================================

- Switched to the new multi-bulk request protocol for all of the commands
  in the Redis 1.2 and Redis 2.0 profiles. Inline and bulk requests are now
  deprecated as they will be removed in future releases of Redis.

- The default server profile is `2.0` (targeting Redis 2.0.x). If you are
  using older versions of Redis, it is highly recommended that you specify
  which server profile the client should use (e.g. `1.2` when connecting
  to instances of Redis 1.2.x).

- Support for Redis 1.0 is now optional and it is provided by requiring
  'Predis_Compatibility.php' before creating an instance of Predis\Client.

- New commands added to the Redis 2.0 profile since Predis 0.5.1:
  - Strings: `SETEX`, `APPEND`, `SUBSTR`
  - ZSets  : `ZCOUNT`, `ZRANK`, `ZUNIONSTORE`, `ZINTERSTORE`, `ZREMBYRANK`,
             `ZREVRANK`
  - Hashes : `HSET`, `HSETNX`, `HMSET`, `HINCRBY`, `HGET`, `HMGET`, `HDEL`,
             `HEXISTS`, `HLEN`, `HKEYS`, `HVALS`, `HGETALL`
  - PubSub : `PUBLISH`, `SUBSCRIBE`, `UNSUBSCRIBE`
  - Misc.  : `DISCARD`, `CONFIG`

- Introduced client-level options with the new `Predis\ClientOptions` class.
  Options can be passed to the constructor of `Predis\Client` in its second
  argument as an array or an instance of Predis\ClientOptions. For brevity's
  sake and compatibility with older versions, the constructor still accepts
  an instance of `Predis\RedisServerProfile` in its second argument. The
  currently supported client options are:

  - `profile` [default: `2.0` as of Predis 0.6.0]: specifies which server
    profile to use when connecting to Redis. This option accepts an instance
    of `Predis\RedisServerProfile` or a string that indicates the version.

  - `key_distribution` [default: `Predis\Distribution\HashRing`]: specifies
    which key distribution strategy to use to distribute keys among the
    servers that compose a cluster. This option accepts an instance of
    `Predis\Distribution\IDistributionStrategy` so that users can implement
    their own key distribution strategy. `Predis\Distribution\KetamaPureRing`
    is an alternative distribution strategy providing a pure-PHP implementation
    of the same algorithm used by libketama.

  - `throw_on_error` [default: `TRUE`]: server errors can optionally be handled
    "silently": instead of throwing an exception, the client returns an error
    response type.

  - `iterable_multibulk` [EXPERIMENTAL - default: `FALSE`]: in addition to the
    classic way of fetching a whole multibulk reply into an array, the client
    can now optionally stream a multibulk reply down to the user code by using
    PHP iterators. It is just a little bit slower, but it can save a lot of
    memory in certain scenarios.

- New parameters for connections:

  - `alias` [default: not set]: every connection can now be identified by an
    alias that is useful to get a specific connections when connected to a
    cluster of Redis servers.
  - `weight` [default: not set]: allows to balance keys asymmetrically across
    multiple servers. This is useful when you have servers with different
    amounts of memory to distribute the load of your keys accordingly.
  - `connection_async` [default: `FALSE`]: estabilish connections to servers
    in a non-blocking way, so that the client is not blocked while the socket
    resource performs the actual connection.
  - `connection_persistent` [default: `FALSE`]: the underlying socket resource
    is left open when a script ends its lifecycle. Persistent connections can
    lead to unpredictable or strange behaviours, so they should be used with
    extreme care.

- Introduced the `Predis\Pipeline\IPipelineExecutor` interface. Classes that
  implements this interface are used internally by the `Predis\CommandPipeline`
  class to change the behaviour of the pipeline when writing/reading commands
  from one or multiple servers. Here is the list of the default executors:

  - `Predis\Pipeline\StandardExecutor`: exceptions generated by server errors
    might be thrown depending on the options passed to the client (see the
    `throw_on_error` client option). Instead, protocol or network errors always
    throw exceptions. This is the default executor for single and clustered
    connections and shares the same behaviour of Predis 0.5.x.
  - `Predis\Pipeline\SafeExecutor`: exceptions generated by server, protocol
    or network errors are not thrown but returned in the response array as
    instances of `Predis\ResponseError` or `Predis\CommunicationException`.
  - `Predis\Pipeline\SafeClusterExecutor`: this executor shares the same
    behaviour of `Predis\Pipeline\SafeExecutor` but it is geared towards
    clustered connections.

- Support for PUB/SUB is handled by the new `Predis\PubSubContext` class, which
  could also be used to build a callback dispatcher for PUB/SUB scenarios.

- When connected to a cluster of connections, it is now possible to get a
  new `Predis\Client` instance for a single connection of the cluster by
  passing its alias/index to the new `Predis\Client::getClientFor()` method.

- `Predis\CommandPipeline` and `Predis\MultiExecBlock` return their instances
  when invokink commands, thus allowing method chaining in pipelines and
  multi-exec blocks.

- `Predis\MultiExecBlock` can handle the new `DISCARD` command.

- Connections now support float values for the `connection_timeout` parameter
  to express timeouts with a microsecond resolution.

- __FIX__: TCP connections now respect the read/write timeout parameter when
  reading the payload of server responses. Previously, `stream_get_contents()`
  was being used internally to read data from a connection but it looks like
  PHP does not honour the specified timeout for socket streams when inside
  this function.

- __FIX__: The `GET` parameter for the `SORT` command now accepts also multiple
  key patterns by passing an array of strings. (ISSUE #1).

* __FIX__: Replies to the `DEL` command return the number of elements deleted
  by the server and not 0 or 1 interpreted as a boolean response. (ISSUE #4).


v0.5.1 (2010-01-23)
===============================================================================

* `RPOPLPUSH` has been changed from bulk command to inline command in Redis
  1.2.1, so `ListPopLastPushHead` now extends `InlineCommand`. The old behavior
  is still available via the `ListPopLastPushHeadBulk` class so that you can
  override the server profile if you need the old (and uncorrect) behaviour
  when connecting to a Redis 1.2.0 instance.

* Added missing support for `BGREWRITEAOF` for Redis >= 1.2.0.

* Implemented a factory method for the `RedisServerProfile` class to ease the
  creation of new server profile instances based on a version string.


v0.5.0 (2010-01-09)
===============================================================================
* First versioned release of Predis
