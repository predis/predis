# Adding commands

When adding a command, follow the existing conventions and update these places:

- **Implementation:** Add `src/Command/Redis/COMMAND.php` with `getId()`, argument
  handling, and response parsing as needed. For commands with keys, extend
  `PrefixableCommand` and implement `prefixKeys()` so only keys are prefixed.
- **Client API:** Add matching `@method` annotations to `src/ClientInterface.php`
  and `src/ClientContextInterface.php`. Keep parameter names, types, and defaults
  consistent; the context interface returns `$this`.
- **Cluster:** Register supported commands in `src/Cluster/ClusterStrategy.php`
  with the correct key extractor. Update the expected command maps in both
  `tests/Predis/Cluster/PredisStrategyTest.php` and `RedisStrategyTest.php`.
- **Replication:** For read commands, check `src/Replication/ReplicationStrategy.php`
  and its tests so the command can be routed to replicas when appropriate.
- **Tests:** Add `tests/Predis/Command/Redis/COMMAND_Test.php` covering arguments,
  optional argument combinations, responses, and key prefixing. Add connected
  tests for RESP2 and RESP3 with the appropriate `@requiresRedisVersion`.
- **Changelog:** Add an entry under `Added` at the top of `CHANGELOG.md`.

Run relevant tests with `vendor/bin/phpunit --exclude-group connected`.
Run connected tests against a disposable Redis instance that supports the
command; see `tests/README.md` for setup.
