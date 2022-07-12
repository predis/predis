# Predis #

[![Software license][ico-license]](LICENSE)
[![Latest stable][ico-version-stable]][link-packagist]
[![Latest development][ico-version-dev]][link-packagist]
[![Monthly installs][ico-downloads-monthly]][link-downloads]
[![Build status][ico-build]][link-actions]

A flexible and feature-complete [Redis](http://redis.io) client for PHP 7.2 and newer.

More details about this project can be found on the [frequently asked questions](FAQ.md).


## Main features ##

- Support for Redis from __3.0__ to __7.0__.
- Support for clustering using client-side sharding and pluggable keyspace distributors.
- Support for [redis-cluster](http://redis.io/topics/cluster-tutorial) (Redis >= 3.0).
- Support for master-slave replication setups and [redis-sentinel](http://redis.io/topics/sentinel).
- Transparent key prefixing of keys using a customizable prefix strategy.
- Command pipelining on both single nodes and clusters (client-side sharding only).
- Abstraction for Redis transactions (Redis >= 2.0) and CAS operations (Redis >= 2.2).
- Abstraction for Lua scripting (Redis >= 2.6) and automatic switching between `EVALSHA` or `EVAL`.
- Abstraction for `SCAN`, `SSCAN`, `ZSCAN` and `HSCAN` (Redis >= 2.8) based on PHP iterators.
- Connections are established lazily by the client upon the first command and can be persisted.
- Connections can be established via TCP/IP (also TLS/SSL-encrypted) or UNIX domain sockets.
- Support for [Webdis](http://webd.is) (requires both `ext-curl` and `ext-phpiredis`).
- Support for custom connection classes for providing different network or protocol backends.
- Flexible system for defining custom commands and override the default ones.


## How to _install_ and use Predis ##

This library can be found on [Packagist](http://packagist.org/packages/predis/predis) for an easier
management of projects dependencies using [Composer](http://packagist.org/about-composer).
Compressed archives of each release are [available on GitHub](https://github.com/predis/predis/releases).

```shell
composer require predis/predis
```


### Loading the library ###

Predis relies on the autoloading features of PHP to load its files when needed and complies with the
[PSR-4 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md).
Autoloading is handled automatically when dependencies are managed through Composer, but it is also
possible to leverage its own autoloader in projects or scripts lacking any autoload facility:

```php
// Prepend a base path if Predis is not available in your "include_path".
require 'Predis/Autoloader.php';

Predis\Autoloader::register();
```


### Connecting to Redis ###

When creating a client instance without passing any connection parameter, Predis assumes `127.0.0.1`
and `6379` as default host and port. The default timeout for the `connect()` operation is 5 seconds:

```php
$client = new Predis\Client();
$client->set('foo', 'bar');
$value = $client->get('foo');
```

Connection parameters can be supplied either in the form of URI strings or named arrays. The latter
is the preferred way to supply parameters, but URI strings can be useful when parameters are read
from non-structured or partially-structured sources:

```php
// Parameters passed using a named array:
$client = new Predis\Client([
    'scheme' => 'tcp',
    'host'   => '10.0.0.1',
    'port'   => 6379,
]);

// Same set of parameters, passed using an URI string:
$client = new Predis\Client('tcp://10.0.0.1:6379');
```

Password protected servers can be accessed by adding `password` to the parameters set. When ACLs are
enabled on Redis >= 6.0, both `username` and `password` are required for user authentication.

It is also possible to connect to local instances of Redis using UNIX domain sockets, in this case
the parameters must use the `unix` scheme and specify a path for the socket file:

```php
$client = new Predis\Client(['scheme' => 'unix', 'path' => '/path/to/redis.sock']);
$client = new Predis\Client('unix:/path/to/redis.sock');
```

The client can leverage TLS/SSL encryption to connect to secured remote Redis instances without the
need to configure an SSL proxy like stunnel. This can be useful when connecting to nodes running on
various cloud hosting providers. Encryption can be enabled with using the `tls` scheme and an array
of suitable [options](http://php.net/manual/context.ssl.php) passed via the `ssl` parameter:

```php
// Named array of connection parameters:
$client = new Predis\Client([
  'scheme' => 'tls',
  'ssl'    => ['cafile' => 'private.pem', 'verify_peer' => true],
]);

// Same set of parameters, but using an URI string:
$client = new Predis\Client('tls://127.0.0.1?ssl[cafile]=private.pem&ssl[verify_peer]=1');
```

The connection schemes [`redis`](http://www.iana.org/assignments/uri-schemes/prov/redis) (alias of
`tcp`) and [`rediss`](http://www.iana.org/assignments/uri-schemes/prov/rediss) (alias of `tls`) are
also supported, with the difference that URI strings containing these schemes are parsed following
the rules described on their respective IANA provisional registration documents.

The actual list of supported connection parameters can vary depending on each connection backend so
it is recommended to refer to their specific documentation or implementation for details.

Predis can aggregate multiple connections when providing an array of connection parameters and the
appropriate option to instruct the client about how to aggregate them (clustering, replication or a
custom aggregation logic). Named arrays and URI strings can be mixed when providing configurations
for each node:

```php
$client = new Predis\Client([
    'tcp://10.0.0.1?alias=first-node', ['host' => '10.0.0.2', 'alias' => 'second-node'],
], [
    'cluster' => 'predis',
]);
```

See the [aggregate connections](#aggregate-connections) section of this document for more details.

Connections to Redis are lazy meaning that the client connects to a server only if and when needed.
While it is recommended to let the client do its own stuff under the hood, there may be times when
it is still desired to have control of when the connection is opened or closed: this can easily be
achieved by invoking `$client->connect()` and `$client->disconnect()`. Please note that the effect
of these methods on aggregate connections may differ depending on each specific implementation.


### Client configuration ###

Many aspects and behaviors of the client can be configured by passing specific client options to the
second argument of `Predis\Client::__construct()`:

```php
$client = new Predis\Client($parameters, ['prefix' => 'sample:']);
```

Options are managed using a mini DI-alike container and their values can be lazily initialized only
when needed. The client options supported by default in Predis are:

  - `prefix`: prefix string applied to every key found in commands.
  - `exceptions`: whether the client should throw or return responses upon Redis errors.
  - `connections`: list of connection backends or a connection factory instance.
  - `cluster`: specifies a cluster backend (`predis`, `redis` or callable).
  - `replication`: specifies a replication backend (`predis`, `sentinel` or callable).
  - `aggregate`: configures the client with a custom aggregate connection (callable).
  - `parameters`: list of default connection parameters for aggregate connections.
  - `commands`: specifies a command factory instance to use through the library.

Users can also provide custom options with values or callable objects (for lazy initialization) that
are stored in the options container for later use through the library.


### Aggregate connections ###

Aggregate connections are the foundation upon which Predis implements clustering and replication and
they are used to group multiple connections to single Redis nodes and hide the specific logic needed
to handle them properly depending on the context. Aggregate connections usually require an array of
connection parameters along with the appropriate client option when creating a new client instance.

#### Cluster ####

Predis can be configured to work in clustering mode with a traditional client-side sharding approach
to create a cluster of independent nodes and distribute the keyspace among them. This approach needs
some sort of external health monitoring of nodes and requires the keyspace to be rebalanced manually
when nodes are added or removed:

```php
$parameters = ['tcp://10.0.0.1', 'tcp://10.0.0.2', 'tcp://10.0.0.3'];
$options    = ['cluster' => 'predis'];

$client = new Predis\Client($parameters);
```

Along with Redis 3.0, a new supervised and coordinated type of clustering was introduced in the form
of [redis-cluster](http://redis.io/topics/cluster-tutorial). This kind of approach uses a different
algorithm to distribute the keyspaces, with Redis nodes coordinating themselves by communicating via
a gossip protocol to handle health status, rebalancing, nodes discovery and request redirection. In
order to connect to a cluster managed by redis-cluster, the client requires a list of its nodes (not
necessarily complete since it will automatically discover new nodes if necessary) and the `cluster`
client options set to `redis`:

```php
$parameters = ['tcp://10.0.0.1', 'tcp://10.0.0.2', 'tcp://10.0.0.3'];
$options    = ['cluster' => 'redis'];

$client = new Predis\Client($parameters, $options);
```

#### Replication ####

The client can be configured to operate in a single master / multiple slaves setup to provide better
service availability. When using replication, Predis recognizes read-only commands and sends them to
a random slave in order to provide some sort of load-balancing and switches to the master as soon as
it detects a command that performs any kind of operation that would end up modifying the keyspace or
the value of a key. Instead of raising a connection error when a slave fails, the client attempts to
fall back to a different slave among the ones provided in the configuration.

The basic configuration needed to use the client in replication mode requires one Redis server to be
identified as the master (this can be done via connection parameters by setting the `role` parameter
to `master`) and one or more slaves (in this case setting `role` to `slave` for slaves is optional):

```php
$parameters = ['tcp://10.0.0.1?role=master', 'tcp://10.0.0.2', 'tcp://10.0.0.3'];
$options    = ['replication' => 'predis'];

$client = new Predis\Client($parameters, $options);
```

The above configuration has a static list of servers and relies entirely on the client's logic, but
it is possible to rely on [`redis-sentinel`](http://redis.io/topics/sentinel) for a more robust HA
environment with sentinel servers acting as a source of authority for clients for service discovery.
The minimum configuration required by the client to work with redis-sentinel is a list of connection
parameters pointing to a bunch of sentinel instances, the `replication` option set to `sentinel` and
the `service` option set to the name of the service:

```php
$sentinels = ['tcp://10.0.0.1', 'tcp://10.0.0.2', 'tcp://10.0.0.3'];
$options   = ['replication' => 'sentinel', 'service' => 'mymaster'];

$client = new Predis\Client($sentinels, $options);
```

If the master and slave nodes are configured to require an authentication from clients, a password
must be provided via the global `parameters` client option. This option can also be used to specify
a different database index. The client options array would then look like this:

```php
$options = [
    'replication' => 'sentinel',
    'service' => 'mymaster',
    'parameters' => [
        'password' => $secretpassword,
        'database' => 10,
    ],
];
```

While Predis is able to distinguish commands performing write and read-only operations, `EVAL` and
`EVALSHA` represent a corner case in which the client switches to the master node because it cannot
tell when a Lua script is safe to be executed on slaves. While this is indeed the default behavior,
when certain Lua scripts do not perform write operations it is possible to provide an hint to tell
the client to stick with slaves for their execution:

```php
$parameters = ['tcp://10.0.0.1?role=master', 'tcp://10.0.0.2', 'tcp://10.0.0.3'];
$options    = ['replication' => function () {
    // Set scripts that won't trigger a switch from a slave to the master node.
    $strategy = new Predis\Replication\ReplicationStrategy();
    $strategy->setScriptReadOnly($LUA_SCRIPT);

    return new Predis\Connection\Replication\MasterSlaveReplication($strategy);
}];

$client = new Predis\Client($parameters, $options);
$client->eval($LUA_SCRIPT, 0);             // Sticks to slave using `eval`...
$client->evalsha(sha1($LUA_SCRIPT), 0);    // ... and `evalsha`, too.
```

The [`examples`](examples/) directory contains a few scripts that demonstrate how the client can be
configured and used to leverage replication in both basic and complex scenarios.


### Command pipelines ###

Pipelining can help with performances when many commands need to be sent to a server by reducing the
latency introduced by network round-trip timings. Pipelining also works with aggregate connections.
The client can execute the pipeline inside a callable block or return a pipeline instance with the
ability to chain commands thanks to its fluent interface:

```php
// Executes a pipeline inside the given callable block:
$responses = $client->pipeline(function ($pipe) {
    for ($i = 0; $i < 1000; $i++) {
        $pipe->set("key:$i", str_pad($i, 4, '0', 0));
        $pipe->get("key:$i");
    }
});

// Returns a pipeline that can be chained thanks to its fluent interface:
$responses = $client->pipeline()->set('foo', 'bar')->get('foo')->execute();
```


### Transactions ###

The client provides an abstraction for Redis transactions based on `MULTI` and `EXEC` with a similar
interface to command pipelines:

```php
// Executes a transaction inside the given callable block:
$responses = $client->transaction(function ($tx) {
    $tx->set('foo', 'bar');
    $tx->get('foo');
});

// Returns a transaction that can be chained thanks to its fluent interface:
$responses = $client->transaction()->set('foo', 'bar')->get('foo')->execute();
```

This abstraction can perform check-and-set operations thanks to `WATCH` and `UNWATCH` and provides
automatic retries of transactions aborted by Redis when `WATCH`ed keys are touched. For an example
of a transaction using CAS you can see [the following example](examples/transaction_using_cas.php).


### Adding new commands ###

While we try to update Predis to stay up to date with all the commands available in Redis, you might
prefer to stick with an old version of the library or provide a different way to filter arguments or
parse responses for specific commands. To achieve that, Predis provides the ability to implement new
command classes to define or override commands in the default command factory used by the client:

```php
// Define a new command by extending Predis\Command\Command:
class BrandNewRedisCommand extends Predis\Command\Command
{
    public function getId()
    {
        return 'NEWCMD';
    }
}

// Inject your command in the current command factory:
$client = new Predis\Client($parameters, [
    'commands' => [
        'newcmd' => 'BrandNewRedisCommand',
    ],
]);

$response = $client->newcmd();
```

There is also a method to send raw commands without filtering their arguments or parsing responses.
Users must provide the list of arguments for the command as an array, following the signatures as
defined by the [Redis documentation for commands](http://redis.io/commands):

```php
$response = $client->executeRaw(['SET', 'foo', 'bar']);
```


### Script commands ###

While it is possible to leverage [Lua scripting](http://redis.io/commands/eval) on Redis 2.6+ using
directly [`EVAL`](http://redis.io/commands/eval) and [`EVALSHA`](http://redis.io/commands/evalsha),
Predis offers script commands as an higher level abstraction built upon them to make things simple.
Script commands can be registered in the command factory used by the client and are accessible as if
they were plain Redis commands, but they define Lua scripts that get transmitted to the server for
remote execution. Internally they use [`EVALSHA`](http://redis.io/commands/evalsha) by default and
identify a script by its SHA1 hash to save bandwidth, but [`EVAL`](http://redis.io/commands/eval)
is used as a fall back when needed:

```php
// Define a new script command by extending Predis\Command\ScriptCommand:
class ListPushRandomValue extends Predis\Command\ScriptCommand
{
    public function getKeysCount()
    {
        return 1;
    }

    public function getScript()
    {
        return <<<LUA
math.randomseed(ARGV[1])
local rnd = tostring(math.random())
redis.call('lpush', KEYS[1], rnd)
return rnd
LUA;
    }
}

// Inject the script command in the current command factory:
$client = new Predis\Client($parameters, [
    'commands' => [
        'lpushrand' => 'ListPushRandomValue',
    ],
]);

$response = $client->lpushrand('random_values', $seed = mt_rand());
```


### Customizable connection backends ###

Predis can use different connection backends to connect to Redis. Two of them leverage a third party
extension such as [phpiredis](https://github.com/nrk/phpiredis) resulting in major performance gains
especially when dealing with big multibulk responses. While one is based on PHP streams, the other
is based on socket resources provided by `ext-socket`. Both support TCP/IP and UNIX domain sockets:

```php
$client = new Predis\Client('tcp://127.0.0.1', [
    'connections' => [
        'tcp'  => 'Predis\Connection\PhpiredisStreamConnection',  // PHP stream resources
        'unix' => 'Predis\Connection\PhpiredisSocketConnection',  // ext-socket resources
    ],
]);
```

The client can also be configured to rely on a [phpiredis](https://github.com/nrk/phpiredis)-backend
by specifying a descriptive string for the `connections` client option. Supported string values are:

- `phpiredis-stream` maps `tcp`, `redis` and `unix` to `Predis\Connection\PhpiredisStreamConnection`
- `phpiredis-socket` maps `tcp`, `redis` and `unix` to `Predis\Connection\PhpiredisSocketConnection`
- `phpiredis` is simply an alias of `phpiredis-stream`

```php
$client = new Predis\Client('tcp://127.0.0.1', ['connections' => 'phpiredis']);
```

Developers can create their own connection classes to support whole new network backends, extend
existing classes or provide completely different implementations. Connection classes must implement
`Predis\Connection\NodeConnectionInterface` or extend `Predis\Connection\AbstractConnection`:

```php
class MyConnectionClass implements Predis\Connection\NodeConnectionInterface
{
    // Implementation goes here...
}

// Use MyConnectionClass to handle connections for the `tcp` scheme:
$client = new Predis\Client('tcp://127.0.0.1', [
    'connections' => ['tcp' => 'MyConnectionClass'],
]);
```

For a more in-depth insight on how to create new connection backends you can refer to the actual
implementation of the standard connection classes available in the `Predis\Connection` namespace.


## Development ##


### Reporting bugs and contributing code ###

Contributions to Predis are highly appreciated either in the form of pull requests for new features,
bug fixes, or just bug reports. We only ask you to adhere to a [basic set of rules](CONTRIBUTING.md)
before submitting your changes or filing bugs on the issue tracker to make it easier for everyone to
stay consistent while working on the project.


### Test suite ###

__ATTENTION__: Do not ever run the test suite shipped with Predis against instances of Redis running
in production environments or containing data you are interested in!

Predis has a comprehensive test suite covering every aspect of the library and that can optionally
perform integration tests against a running instance of Redis (required >= 2.4.0 in order to verify
the correct behavior of the implementation of each command. Integration tests for unsupported Redis
commands are automatically skipped. If you do not have Redis up and running, integration tests can
be disabled. See [the tests README](tests/README.md) for more details about testing this library.

Predis uses GitHub Actions for continuous integration and the history for past and current builds can be
found [on its actions page](https://github.com/predis/predis/actions).


## Other ##


### Project related links ###

- [Source code](https://github.com/predis/predis)
- [Wiki](https://github.com/predis/predis/wiki)
- [Issue tracker](https://github.com/predis/predis/issues)


### Author ###

- [Daniele Alessandri](mailto:suppakilla@gmail.com) ([twitter](http://twitter.com/JoL1hAHN))
- [Till Krüss](https://till.im) ([Twitter](http://twitter.com/tillkruss))


### License ###

The code for Predis is distributed under the terms of the MIT license (see [LICENSE](LICENSE)).

[ico-license]: https://img.shields.io/github/license/predis/predis.svg?style=flat-square
[ico-version-stable]: https://img.shields.io/packagist/v/predis/predis.svg?style=flat-square
[ico-version-dev]: https://img.shields.io/packagist/vpre/predis/predis.svg?style=flat-square
[ico-downloads-monthly]: https://img.shields.io/packagist/dm/predis/predis.svg?style=flat-square
[ico-build]: https://img.shields.io/github/workflow/status/predis/predis/Tests/main?style=flat-square

[link-packagist]: https://packagist.org/packages/predis/predis
[link-actions]: https://github.com/predis/predis/actions
[link-downloads]: https://packagist.org/packages/predis/predis/stats
