# Adding new command best practices

## API specification

The Redis API is specified in the [Redis documentation](https://redis.io/commands). This is a source of the truth
for all command related information. However, Redis is a living project and new commands are added all the time.
New command might be not yet available in the documentation. In this case the developer needs to create a new
command specification from `.augment/commands/add-command/command-specification-template.md` template.

## Expose commands API via Predis

Commands public API is defined in `src/ClientInterface.php` and `src/ClientContextInterface.php` files. New
commands should be added to these files and sorted alphabetically by command name. The methods defined by
docblock in the interfaces are "virtual" methods and handled by `__call()` magic method in `Client` class to
map them to actual command instances defined in `src/Command/Redis` directory.

### Name convention

Command methods in the API should be named lowercase exactly as the command is named in Redis documentation.
However, there are few exceptions to this rule:
  * Commands which are reserved PHP keywords (like `function`) should be suffixed with underscore to make them
    valid PHP method names (like `function_`).
  * Module commands that contains prefixes (like `JSON.SET`) should be named without dot (like `jsonset`).
  * Container commands that contains subcommands (like `XGROUP CREATE`) should represent container command
    as property in `Client` class and subcommand as method in container class. For example `XGROUP CREATE` should be
    accessed as `$client->xgroup->create()`.

### Container commands

If command contains subcommands (like `XGROUP CREATE`, `XGROUP DESTROY`, `XGROUP SETID`), then it should be
defined as container command. Container command class should be defined in `src/Command/Container` directory and
implement `Predis\Command\Container\ContainerInterface`. Container command should have a method for each
subcommand that it contains. The method should have the same name as the subcommand. This approach allows to provide
a better API for subcommands.

At the end command still needs to be defined in `src/Command/Redis` directory as container class just adds an argument
with subcommand name and suits better for autocompletion and readability. Arguments and response transformation
still needs to be handled `Command::setArguments()` and `Command::parseResponse()` methods.

### Files structure

```
src/
├── Command/
│   ├── Redis/           # Command classes for Redis commands
│   │   ├── JSON/        # Module commands
│   │   │   ├── SET.php
│   │   │   └── GET.php
│   │   ├── XGROUP.php
│   │   └── PING.php
│   ├── Container/       # Container commands
│   │   ├── XGROUP.php
│   │   └── JSON.php
├── ClientInterface.php  # Public API for Client class
└── ClientContextInterface.php
```

## Types mapping
To understand how does Redis types defined by RESP2 and RESP3 protocol maps to PHP types,
see `src/Protocol/Parser/Strategy/`.

To keep RESP2 and RESP3 backward-compatible in terms of dictionaries, we use `arrayToDictionary()` method
from `src/Command/Redis/Utils/CommandUtility.php`.

## Arguments transformation

Some of the Redis commands may have a complex API structure, so we need to make it user-friendly and apply
some transformation within `Command::setArguments()` method. For example, some commands may need to have
a COUNT argument for aggregated types followed by number of arguments and arguments itself, in this case
we hide this complexity from user and instead expose argument as array in public API and transform it
to Redis-friendly format in `setArguments()` method.

```php
/**
 * @method array xrange(string $key, string $start, string $end, ?int $count = null)
 */
public function setArguments(array $arguments)
{
    if (count($arguments) === 4) {
        $arguments[] = $arguments[3];
        $arguments[3] = 'COUNT';
    }

    parent::setArguments($arguments);
}
```

In terms of required and optional arguments we follow the specification and trying to reflect it as close
as possible.

Some of the most complex commands may require separate QueryBuilder objects to be built (f.e FT.SEARCH, FT.HYBRID)
see `src/Command/Argument/Search` for examples.

### Testing

To test the command, you need to create a test file in `tests/Predis/Command/Redis` directory. The test file
should be named like `CommandName_Test.php` and contain a class named like `CommandName_Test` that extends
`PredisCommandTestCase`.

Within a single test class we keep unit and integration tests together. Unit tests are executed without
Redis server and integration tests are executed against Redis server. To mark integration test, use
`@group connected` annotation. We have a special annotations to restrict test execution to specific
Redis version or module version. For example `@requiresRedisVersion >= 7.2.0` or module version
`@requiresRedisJsonVersion >= 2.0.0` (for Redis-stack).

Usually, unit tests mostly tests arguments and response transformations, whereas integration tests
sends actual command to Redis server and ensure that we support all arguments and response types including
RESP2 and RESP3.

Since, we're aiming for 100% test coverage we need to ensure that all branches of code are executed.
To ensure that every argument is transformed correctly, we need to test all possible combinations of
arguments, we're using a dataProviders to provide test data for arguments, this test calls `testFilterArguments`
with different arguments and checks if the command is transformed correctly.

We also want to test negative scenarios if we handle it and throws an exception.
