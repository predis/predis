<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/shared.php';

// Predis allows to set Lua scripts as read-only operations for replication.
// This works for both EVAL and EVALSHA and also for the client-side abstraction
// built upon them (Predis\Command\ScriptCommand). This example shows a slightly
// more complex configuration that injects a new script command in the command
// factory used by the client and marks it as a read-only operation so that it
// will be executed on slaves.

use Predis\Command\ScriptCommand;
use Predis\Connection\Replication\MasterSlaveReplication;
use Predis\Replication\ReplicationStrategy;

// ------------------------------------------------------------------------- //

// Define a new script command that returns all the fields of a variable number
// of hashes with a single roundtrip.

class HashMultipleGetAll extends ScriptCommand
{
    public const BODY = <<<LUA
local hashes = {}
for _, key in pairs(KEYS) do
    table.insert(hashes, key)
    table.insert(hashes, redis.call('hgetall', key))
end
return hashes
LUA;

    public function getScript()
    {
        return self::BODY;
    }
}

// ------------------------------------------------------------------------- //

$parameters = [
    'tcp://127.0.0.1:6381?role=master&database=15',
    'tcp://127.0.0.1:6382?role=slave&alias=slave-01&database=15',
];

$options = [
    'commands' => [
        'hmgetall' => 'HashMultipleGetAll',
    ],
    'replication' => function () {
        $strategy = new ReplicationStrategy();
        $strategy->setScriptReadOnly(HashMultipleGetAll::BODY);

        $replication = new MasterSlaveReplication($strategy);

        return $replication;
    },
];

// ------------------------------------------------------------------------- //

$client = new Predis\Client($parameters, $options);

// Execute the following commands on the master server using redis-cli:
// $ ./redis-cli HMSET metavars foo bar hoge piyo
// $ ./redis-cli HMSET servers master host1 slave host2

$hashes = $client->hmgetall('metavars', 'servers');

$replication = $client->getConnection();
$stillOnSlave = $replication->getCurrent() === $replication->getConnectionByAlias('slave-01');

echo 'Is still on slave? ', $stillOnSlave ? 'YES!' : 'NO!', PHP_EOL;
var_export($hashes);
