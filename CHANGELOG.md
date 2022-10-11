## Changelog

## v2.0.3 (2022-10-11)

- Improved PHP 8.2 support
- Call `is_resource()` before reading/writing
- Added partial support for Redis Stream commands
- Fixed Sentinel authentication issue

## v2.0.2 (2022-09-06)

- Fixed PHP 8.2 deprecation notice: Use of "static" in callables

## v2.0.1 (2022-09-04)

- Added retry interval to `RedisCluster` with a default of `10ms`
- Avoid PHP 8.2 warning in `Connection\Parameters`
- Fixed Sentinel reconnect issue in long-running PHP processes

## v2.0.0 (2022-06-08)

- Dropped support for PHP 7.1 and older

- Accepted values for some client options have changed, this is the new list of
  accepted values:

  - `aggregate`: callable returning an aggregate connection.
  - `cluster`: string value (`predis`, `redis`), callable returning an aggregate
    connection.
  - `replication`: string value (`predis`, `sentinel`), callable returning an
     aggregate connection.
  - `commands`: command factory, named array mapping command IDs to PHP classes,
    callable returning a command factory or a named array.
  - `connections`: connection factory, callable object returning a connection
    factory, named array mapping URI schemes to PHP classes, string identifying
    a supported combination of configurations for the connection factory.
  - `prefix`: string value, command processor, callable.
  - `exceptions`: boolean.

  Note that both the `cluster` and `replication` options now return a closure
  acting as initializer instead of an aggregate connection instance.

- The `connections` client option now accepts certain string values identifying
  certain combinations of configurations for the connection factory. Currenlty
  this is used to provide a short way to configure Predis to load our phpiredis
  based connection backends simply, accepted values are:

  - `phpiredis-stream` maps `Phpiredis\Connection\PhpiredisStreamConnection` to
    `tcp`, `redis`, `unix` URI schemes.
  - `phpiredis-socket` maps `Phpiredis\Connection\PhpiredisSocketConnection` to
    `tcp`, `redis`, `unix` URI schemes.
  - `phpiredis-stream` is simply an alias of `phpiredis-stream`.

- Added the new `Predis\Cluster\Hash\PhpiredisCRC16` class using ext-phpiredis
  to speed-up the generation of the CRC16 hash of keys for redis-cluster. Predis
  automatically uses this class when ext-phpiredis is loaded, but it is possible
  to configure the hash generator using the new `crc16` client option (accepted
  values `predis`, `phpiredis` or an hash generator instance).

- Replication backends now use the `role` parameter instead of `alias` in order
  to distinguish the role of a connection. Accepted values are `master`, `slave`
  and, for redis-sentinel, `sentinel`. This led to a redesign of how connections
  can be retrieved from replication backends: the method getConnectionById() now
  retrieves a connection only by its ID (ip:port pair), to get a connection by
  its alias there is the new method getConnectionByAlias(). This method is not
  supported by the redis-sentinel backend due to its dynamic nature (connections
  are retrieved and initialized at runtime from sentinels) but it is possible to
  get a single connection from the pool by using its ID. It is also possible to
  retrive a connection by role using the method getConnectionByRole().

- The concept of connection ID (ip:port pair) and connection alias (the `alias`
  parameter) in `Predis\Connection\Cluster\PredisCluster` has been separated.
  This change does not affect distribution and it is safe for existing clusters.

- Client option classes now live in the `Predis\Configuration\Option` namespace.

- Classes for Redis commands have been moved into the new `Predis\Command\Redis`
  namespace and each class name mirrors the respective Redis command ID.

- The concept of server profiles is gone, the library now uses a single command
  factory to create instances of commands classes. The `profile` option has been
  replaced by the `commands` option accepting `Predis\Command\FactoryInterface`
  to customize the underlying command factory. The default command factory class
  used by Predis is `Predis\Command\RedisFactory` and it still allows developers
  to define or override commands with their own implementations. In addition to
  that, `Predis\Command\RedisFactory` relies on a convention-over-configuration
  approach by looking for a suitable class with the same name as the command ID
  in the `Predis\Command\Redis` when the internal class map does not contain a
  class associated.

- The method `Predis\Client::getClientFor($connectionID)` has been replaced by
  `getClientBy($selector, $value, $callable = null)` which is more flexible as
  it is not limited to picking a connection from the underlying replication or
  cluster backend by ID, but allows users to specify a `$selector` that can be
  either `id` (the old behavior), `key`, `slot` or `command`. The client uses
  duck-typing instead of type-checking to verify that the underlying connection
  implements a method that matches the specified selector which means that some
  selectors may not be available to all kinds of connection backends.

- The method `Predis\Client::getConnectionById($connectionID)` has been removed.

- Changed the signature for the constructor of `Predis\Command\RawCommand`.

- The `Predis\Connection\Aggregate` namespace has been split into two separate
  namespaces for cluster backends (`Predis\Connection\Cluster`) and replication
  backends (`Predis\Connection\Replication`).

- The method `Predis\Connection\AggregateConnectionInterface::getConnection()`
  has been renamed to `getConnectionByCommand()`.

- The methods `switchToMaster()` and `switchToSlave()` have been promoted to be
  part of `Predis\Connection\Replication\ReplicationInterface` while the method
  `switchTo($connection)` has been removed from it.

- The method `Predis\Connection\Cluster\PredisCluster::executeCommandOnNodes()`
  has been removed as it is possible to achieve the same by iterating over the
  connection or, even better, over the client instance in order to execute the
  same command against all of the registered connections.

- The class `Predis\CommunicationException` now uses the correct default types
  for the `$message` (string) and `$code` (integer) parameters.

- The method `onConnectionError()` in `Predis\Connection\AbstractConnection`
  class now passes the second argument as an integer value `0` as its default
  value instead of `null`.

- Support Pub/Sub and Pipelines when using replication

- The class `Predis\Transaction\AbortedMultiExecException` now uses the correct 
  default types for the `$code` (integer) parameter.

- __FIX__: using `strval` in `getScanOptions()` method, part of
  `Predis\Collection\Iterator\CursorBasedIterator` to make sure we retrieve the
  string value of `$this->match` and not passing `null` to `strlen()` function.

- __FIX__: the value returned from `getArgument()` in `isReadOperation()` method,
  part of `Predis\Replication\ReplicationStrategy` class, is checked to not pass 
  `null` to `sha1` function.

- __FIX__: the value returned from `getArgument()` in `parseResponse()`method,
  part of `Predis\Command\Redis\SENTINEL` class, is checked to not pass `null`
  to `strtolower()` function.

## v2.0.0-beta.1 (2022-05-26)

Same as v2.0.0
