## Changelog
## Unreleased
- Fixed Sentinel getParameters() executed on string configuration (#1649)

## v3.4.1 (2026-02-23)
### Added
- Made `H(P)TTL` commands prefixable (#1639)
- Made `(B)LMPOP` commands prefixable (#1643)

### Fixed
- Fixed Sentinel `getParameter()` call on array error (#2423)
- Removed deprecated `static` from callables (#1642)

## v3.4.0 (2026-02-11)
### Added
- Added optional retry support (#1616)
- Added support for `VRANGE` command (#1623)
- Added support for idempotent stream API (#1632)
- Added support for `HOTKEYS` container command (#1630)

### Fixed
- Fixed `[L|R]PUSHX` variadic arguments normalization (#1633)
- Fixed wrong `@param` annotation in `Parameters` (#1614)
- Made `ZRANDMEMBER` prefixable (#1621)
- Improve connection handshake by pipelining commands (#1622)

## v3.3.0 (2025-11-24)
### Added
- Added cluster support for `XADD`, `XDEL` and `XRANGE` (#1587)
- Added prefixable interface for `HEXPIRE` and `HEXPIRETIME` (#1592)
- Added new experimental CAS/CAD functionality (#1609)
- Added temporary `XREADGROUP_CLAIM` command (#1608)
- Added support for `MSET` command (#1610)
- Added experimental support for `FT.HYBRID` (#1607)

### Changed
- Improved compatiblity with Relay (#1597)
- Refactor pipeline data writing depends on connection type (#1586)

## v3.2.0 (2025-08-05)
### Added
- Added support for `XDELEX` and `XACKDEL` (#1580)
- Added missing VSIM argument (#1582)

### Changed
- Extended `XTRIM` and `XADD` commands with new parameters (#1580)

### Maintenance
- Updated Redis 8.2 test image (#1583)
- Added test coverage for updated Vamana (#1584)

## v3.1.0 (2025-07-22)
### Added
- Add experimental support for vector sets commands (#1550)
- Added support for `XACK` command (#1555)
- Added support for `XCLAIM` command (#1557)
- Added support for `XPENDING` command (#1558)
- Added support for `XSETID` command (#1559)
- Added validation and support for the new `BITOP` command operations (#1566)

### Changed
- Handle and retry `LOADING` errors from Sentinel replicas (#1536)
- Retry all exceptions from Sentinel replicas (#1577)

### Fixed
- Fixed PHP 8.4 deprecated call to `stream_context_set_option()` (#1545)
- Fixed return type for `ZCOUNT` to be `int` (#1547)
- Fixed throwing `CommunicationException` when stream is EOF (#1548)
- Removed automatic `conn_uid` parameter assignment (#1552)
- Fixed wrong command API call on prefix processing (#1554)
- Fixed `XREAD` response parsing while read null (#1563)
- Fixed `XINFO` command responses parsing (#1560)
- Marked missing commands as Prefixable (#1576)

## v3.0.1 (2025-05-16)
### Fixed
- Send `default` user if only password is used for authentication (#1535)

## v3.0.0 (2025-05-02)
### Added
- ⚠️ Added support for transactions for clustered connections (#1497)
- Added support for RESP3 communication protocol (#1047)
- Added support for RESP3 push notifications (#1316)
- Added support for Sharded Pub/Sub (#1303)
- Added support for `XGROUP` commands (#1324)
- Added support for `XREADGROUP` command (#1327)
- Added support for `XAUTOCLAIM` command (#1328)
- Added support for `XINFO` commands (#1331)
- Added support for Redis 7.4 `XREAD` and `HSCAN` interface changes (#1452)

### Changed
- ⚠️ Refactored Relay integration (#1423)
- ⚠️ Changed interface of `CLIENT` command (#1337)
- ⚠️ Changed interfaces for commands to support Redis 6.2 — 7.0 arguments (#1330)
- ⚠️ Changed default search `DIALECT` to `2` (#1516)
- ⚠️ Implemented PSR-7 compatible stream abstraction (#1450)
- ⚠️ Improved pipeline abstractions (#1438)
- ⚠️ Improved connection handshake (#1431)

### Fixed
- Fixed single persistent connection using the same resource (#1512)

### Maintenance
- Revisit incompatible tests against Relay (#1537)

## v2.4.0 (2025-04-30)
### Added
- Added new hash-field expiration commands (#1520)
- Added missing `FT._LIST` and `BITFIELD_RO` commands (#1521)

### Changed
- Update `WATCH` command to accept `string|string[]` (#1476)
- Optimize cluster slotmap with compact slot range object (#1493)

### Fixed
- Fixed `EVAL_RO` cluster support (#1449)
- Fixed PHP 8.4 compatibility with `stream_context_set_option()` (#1503)
- Prevent named arguments runtime failure (#1509)
- Mark `GEOSEARCH` as read-only to ensure execution on replica (#1481)
- Fixed protocol loss during redis cluster `MOVED` / `ASK` (#1530)

### Maintenance
- Added CI testing with Redis 8.0 (#1510)
- Added test coverage for compatibility with Redis 8.0 (#1513)
- Use parallel on PHP-CS-Fixer (#1489)

## v2.3.0 (2024-11-21)
### Added
- Added `GeoShapeField` field (#1467)
- Added hash expiration commands (#1456)
- Added support for time series `IGNORE` filter (#1458)
- Added `XREAD` commands (#1459)
- Added `NOVALUES` argument support to `HSCAN` (#1459)
- Added support for search `INDEXMISSING` and `INDEXEMPTY` arguments (#1464)

### Changed
- Explicitly mark nullable parameters as nullable (#1448)
- Filter out available replicas based on link status flag (#1440)
- Respect `prefix` for `ZPOPMIN`, `ZPOPMAX`, `ZMSCORE`, `LMOVE`, `BLMOVE`, `SMISMEMBER` and `GEOSEARCH` (#1451, #1453, #1455, #1468)
- Updated test infrastructure (#1510)
-
## v2.2.0 (2023-06-14)

Predis v2.2.0 introduces official support for [Redis Stack](https://redis.io/docs/stack/) as well as a [Relay](https://github.com/cachewerk/relay) integration for substantially [faster read performance](https://github.com/predis/predis/wiki/Using-Relay).

### Added
- Added support for [Relay](https://github.com/predis/predis/wiki/Using-Relay) (#1263)
- Added support for `FCALL_RO` command (#1191)
- Added support for Redis `JSON`, `Bloom`, `Search` and `TimeSeries`  module (#1253)
- Added support for `ACL SETUSER, GETUSER, DRYRUN` commands (#1193)

### Changed
- Minor code style and type-hint changes (#1311)

### Fixed
- Fixed prefixes for `XTRIM` and `XREVRANGE` commands (#1230)
- Fixed `fclose()` being called on invalid stream resource (#1199)
- Fixed `BitByte` and `ExpireOptions` traits skip processing on null values (#1169)
- Fixed missing `@return` annotations (#1265)
- Fixed `GETDEL` prefixing (#1306)

## v2.1.2 (2023-03-02)

### Added
- Added stream commands to `KeyPrefixProcessor` (#1051)
- Added `ReplicationStrategy::$loadBalancing` option to disable replica reads (#1168)
- Added support for `FCALL` and `FUNCTIONS` commands (#1049)
- Added support for `PEXPIRETIME` command (#1031)
- Added support for `EXPIRETIME` command (#1029)
- Added support for `EVAL_RO` command (#1032)
- Added support for `LCS` command (#1035)
- Added support for `SORT_RO` command (#1044)
- Added support for `SINTERCARD` command (#1027)
- Added support for `EVALSHA_RO` command (#1034)
- Added support for new arguments for `BITPOS` and `BITCOUNT` commands (#1045)
- Added support for new arguments for `EXPIRE` and `EXPIREAT` commands (#1046)

### Bug Fixes
- Fixed deprecated function call syntax

### Deprecated
- Further deprecated phpiredis and webdis integration (#1179)

### Maintenance
- Applied coding standards
- Pass PHPStan level 2

## v2.1.1 (2023-01-17)

### Bug Fixes
- Fix `@template` in `Predis\Client` (#1017)
- Fix support options array in `ZINTERSTORE` and `ZUNIONSTORE` (#1018)

### Deprecated
- Deprecated phpiredis and webdis connections

## v2.1.0 (2023-01-16)

### New Features
- Implemented `GETEX` command (#872)
- Implemented `GETDEL` command (#869)
- Implemented `COPY` command (#866)
- Implemented `FAILOVER` command (#875)
- Implemented `LMOVE` command (#863)
- Implemented `LMPOP` command (#1013)
- Implemented `HRANDFIELD` command (#870)
- Implemented `SMISMEMBER` command (#871)
- Implemented `ZMPOP` command (#831)
- Implemented `BLMOVE` command (#865)
- Implemented `BLMPOP` command (#1015)
- Implemented `BZMPOP` command (#833)
- Implemented `BZPOPMIN` command (#862)
- Implemented `BZPOPMAX` command (#864)
- Implemented `ZUNION` command (#860)
- Implemented `ZINTER` command (#859)
- Implemented `ZINTERCARD` command (#861)
- Implemented `ZRANGESTORE` command (#829)
- Implemented `ZDIFFSTORE` command (#828)
- Implemented `ZDIFF` command (#826)
- Implemented `ZRANDMEMBER` command (#825)
- Implemented `ZMSCORE` (#823)
- Implemented `GEOSEARCH` command (#867)
- Implemented `GEOSEARCHSTORE` command (#873)

### Bug Fixes
- Added annotations to suppress PHP 8.1 return type deprecation warning (#810)

### Maintenance
- Added mixin annotations for traits (#835)

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
  certain combinations of configurations for the connection factory. Currently
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
  retrieve a connection by role using the method getConnectionByRole().

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
