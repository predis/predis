# About testing Predis #

__ATTENTION__: Do not ever ever run this test suite against instances of Redis running in production
environments or containing data you are interested in! If you still want to test this library on a
production server without hitting the database, please read ahead about how to disable integration
tests.

Predis ships a comprehensive test suite that uses __PHPUnit__ to cover every aspect of the library.
The suite is organized into several unit groups with the PHPUnit `@group` annotation which makes it
possible to run only selected groups of tests. The main groups are:

  - __disconnected__: generic tests verifying the correct behaviour of the library without requiring
    an active connection to Redis.
  - __connected__: integration tests that require an active connection to Redis
  - __commands__: tests for the implementation of Redis commands.
  - __slow__: tests that might slow down the execution of the test suite (either __connected__ or
    __disconnected__).

A list of all the available groups in the suite can be obtained by running:

```bash
$ phpunit --list-groups
```

Groups of tests can be disabled or enabled via the XML configuration file or the standard command
line test runner. Please note that due to a bug in PHPUnit, older versions ignore the `--group`
option when the group is excluded in the XML configuration file. More details about this issue are
available on [PHPUnit's bug tracker](http://github.com/sebastianbergmann/phpunit/issues/320).

Certain groups of tests requiring native extensions, such as `ext-relay`, are
disabled by default in the configuration file. To enable these groups of tests you should remove
them from the exclusion list in `phpunit.xml`.

### Combining groups for inclusion or exclusion with the command-line runner ###

```bash
$ phpunit --group disconnected --exclude-group commands,slow
```

### Integration tests ###

The suite performs integration tests against a running instance of Redis (>= 2.4.0) to verify the
correct behavior of the implementation of each command and certain abstractions implemented in the
library that depend on them. These tests are identified by the __connected__ group.

Integration tests for commands that are not supported by the running instance of Redis are marked as
__skipped__ automatically.

If you do not have a Redis instance up and running or available for testing, you can completely
disable integration tests by excluding the __connected__ group:

```bash
$ phpunit --exclude-group connected
```

### Slow tests ###

Certain tests can slow down the execution of the suite. These tests can be disabled by excluding the
__slow__ group:

```bash
$ phpunit --exclude-group slow
```

### Testing Redis commands ###

We also provide an helper script in the `bin` directory that can be used to automatically generate a
file with the skeleton of a test case to test a Redis command by specifying the name of the class
in the `Predis\Command\Redis` namespace (only classes in this namespace are considered valid).
For example to generate a test case for `SET` (represented by the `Predis\Command\Redis\SET` class):

```bash
$ ./bin/create-command-test --class=SET --realm=string
```

Each command must have a realm specified by the `--realm` command line argument using a value that
matches its group, as defined by the Redis documentation. Valid realms are:

- `string`
- `list`
- `set`
- `sorted_set`
- `hash`
- `geo`
- `stream`
- `hyperloglog`
- `keys`
- `scripting`
- `pubsub`
- `transaction`
- `cluster`
- `server`
- `connection`

When unsure about which value to use for `--realm` for a specific command, you can just infer it by
searching in [`commands.json`](https://github.com/redis/redis-doc/blob/master/commands.json) for the
`group` attribute of the corresponding command as use its value.
