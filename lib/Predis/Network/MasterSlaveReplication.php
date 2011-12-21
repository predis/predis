<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Network;

use Predis\Commands\ICommand;
use Predis\NotSupportedException;

/**
 * Defines the standard virtual connection class that is used
 * by Predis to handle replication with a group of servers in
 * a master/slave configuration.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class MasterSlaveReplication implements IConnectionReplication
{
    private $disallowed = array();
    private $readonly = array();
    private $readonlySHA1 = array();
    private $current = null;
    private $master = null;
    private $slaves = array();

    /**
     *
     */
    public function __construct()
    {
        $this->disallowed = $this->getDisallowedOperations();
        $this->readonly = $this->getReadOnlyOperations();
    }

    /**
     * Checks if one master and at least one slave have been defined.
     */
    protected function check()
    {
        if (!isset($this->master) || !$this->slaves) {
            throw new \RuntimeException('Replication needs a master and at least one slave.');
        }
    }

    /**
     * Resets the connection state.
     */
    protected function reset()
    {
        $this->current = null;
    }

    /**
     * {@inheritdoc}
     */
    public function add(IConnectionSingle $connection)
    {
        $alias = $connection->getParameters()->alias;

        if ($alias === 'master') {
            $this->master = $connection;
        }
        else {
            $this->slaves[$alias ?: count($this->slaves)] = $connection;
        }

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(IConnectionSingle $connection)
    {
        if ($connection->getParameters()->alias === 'master') {
            $this->master = null;
            $this->reset();

            return true;
        }
        else {
            if (($id = array_search($connection, $this->slaves, true)) !== false) {
                unset($this->slaves[$id]);
                $this->reset();

                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection(ICommand $command)
    {
        if ($this->current === null) {
            $this->check();
            $this->current = $this->isReadOperation($command) ? $this->pickSlave() : $this->master;

            return $this->current;
        }

        if ($this->current === $this->master) {
            return $this->current;
        }

        if (!$this->isReadOperation($command)) {
            $this->current = $this->master;
        }

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectionById($connectionId)
    {
        if ($connectionId === 'master') {
            return $this->master;
        }
        if (isset($this->slaves[$connectionId])) {
            return $this->slaves[$connectionId];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function switchTo($connection)
    {
        $this->check();

        if (!$connection instanceof IConnectionSingle) {
            $connection = $this->getConnectionById($connection);
        }
        if ($connection !== $this->master && !in_array($connection, $this->slaves, true)) {
            throw new \InvalidArgumentException('The specified connection is not valid.');
        }

        $this->current = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlaves()
    {
        return array_values($this->slaves);
    }

    /**
     * Returns a random slave.
     *
     * @return IConnectionSingle
     */
    protected function pickSlave()
    {
        return $this->slaves[array_rand($this->slaves)];
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->current ? $this->current->isConnected() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ($this->current === null) {
            $this->check();
            $this->current = $this->pickSlave();
        }

        $this->current->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->master) {
            $this->master->disconnect();
        }
        foreach ($this->slaves as $connection) {
            $connection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeCommand(ICommand $command)
    {
        $this->getConnection($command)->writeCommand($command);
    }

    /**
     * {@inheritdoc}
     */
    public function readResponse(ICommand $command)
    {
        return $this->getConnection($command)->readResponse($command);
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ICommand $command)
    {
        return $this->getConnection($command)->executeCommand($command);
    }

    /**
     * Returns if the specified command performs a read-only operation
     * against a key stored on Redis.
     *
     * @param ICommand $command Instance of Redis command.
     * @return Boolean
     */
    protected function isReadOperation(ICommand $command)
    {
        if (isset($this->disallowed[$id = $command->getId()])) {
            throw new NotSupportedException("The command $id is not allowed in replication mode");
        }

        if (isset($this->readonly[$id])) {
            if (true === $readonly = $this->readonly[$id]) {
                return true;
            }

            return $readonly($command);
        }

        if (($eval = $id === 'EVAL') || $id === 'EVALSHA') {
            $sha1 = $eval ? sha1($command->getArgument(0)) : $command->getArgument(0);

            if (isset($this->readonlySHA1[$sha1])) {
                if (true === $readonly = $this->readonlySHA1[$sha1]) {
                    return true;
                }

                return $readonly($command);
            }
        }

        return false;
    }

    /**
     * Marks a command as a read-only operation. When the behaviour of a
     * command can be decided only at runtime depending on its arguments,
     * a callable object can be provided to dinamically check if the passed
     * instance of a command performs write operations or not.
     *
     * @param string $commandID ID of the command.
     * @param mixed $readonly A boolean or a callable object.
     */
    public function setCommandReadOnly($commandID, $readonly = true)
    {
        $commandID = strtoupper($commandID);

        if ($readonly) {
            $this->readonly[$commandID] = $readonly;
        }
        else {
            unset($this->readonly[$commandID]);
        }
    }

    /**
     * Marks a Lua script for EVAL and EVALSHA as a read-only operation. When
     * the behaviour of a script can be decided only at runtime depending on
     * its arguments, a callable object can be provided to dinamically check
     * if the passed instance of EVAL or EVALSHA performs write operations or
     * not.
     *
     * @param string $script Body of the Lua script.
     * @param mixed $readonly A boolean or a callable object.
     */
    public function setScriptReadOnly($script, $readonly = true)
    {
        $sha1 = sha1($script);

        if ($readonly) {
            $this->readonlySHA1[$sha1] = $readonly;
        }
        else {
            unset($this->readonlySHA1[$sha1]);
        }
    }

    /**
     * Returns the default list of disallowed commands.
     *
     * @return array
     */
    protected function getDisallowedOperations()
    {
        return array(
            'SHUTDOWN'          => true,
            'INFO'              => true,
            'DBSIZE'            => true,
            'LASTSAVE'          => true,
            'CONFIG'            => true,
            'MONITOR'           => true,
            'SLAVEOF'           => true,
            'SAVE'              => true,
            'BGSAVE'            => true,
            'BGREWRITEAOF'      => true,
            'SLOWLOG'           => true,
        );
    }

    /**
     * Returns the default list of commands performing read-only operations.
     *
     * @return array
     */
    protected function getReadOnlyOperations()
    {
        return array(
            'EXISTS'            => true,
            'TYPE'              => true,
            'KEYS'              => true,
            'RANDOMKEY'         => true,
            'TTL'               => true,
            'GET'               => true,
            'MGET'              => true,
            'SUBSTR'            => true,
            'STRLEN'            => true,
            'GETRANGE'          => true,
            'GETBIT'            => true,
            'LLEN'              => true,
            'LRANGE'            => true,
            'LINDEX'            => true,
            'SCARD'             => true,
            'SISMEMBER'         => true,
            'SINTER'            => true,
            'SUNION'            => true,
            'SDIFF'             => true,
            'SMEMBERS'          => true,
            'SRANDMEMBER'       => true,
            'ZRANGE'            => true,
            'ZREVRANGE'         => true,
            'ZRANGEBYSCORE'     => true,
            'ZREVRANGEBYSCORE'  => true,
            'ZCARD'             => true,
            'ZSCORE'            => true,
            'ZCOUNT'            => true,
            'ZRANK'             => true,
            'ZREVRANK'          => true,
            'HGET'              => true,
            'HMGET'             => true,
            'HEXISTS'           => true,
            'HLEN'              => true,
            'HKEYS'             => true,
            'HVELS'             => true,
            'HGETALL'           => true,
            'PING'              => true,
            'AUTH'              => true,
            'SELECT'            => true,
            'ECHO'              => true,
            'QUIT'              => true,
            'OBJECT'            => true,
            'SORT'              => function(ICommand $command) {
                $arguments = $command->getArguments();
                return ($c = count($arguments)) === 1 ? true : $arguments[$c - 2] !== 'STORE';
            },
        );
    }
}
