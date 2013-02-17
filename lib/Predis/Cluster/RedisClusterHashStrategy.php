<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use Predis\Cluster\Hash\CRC16HashGenerator;
use Predis\Command\CommandInterface;

/**
 * Default class used by Predis to calculate hashes out of keys of
 * commands supported by redis-cluster.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisClusterHashStrategy implements CommandHashStrategyInterface
{
    private $commands;
    private $hashGenerator;

    /**
     *
     */
    public function __construct()
    {
        $this->commands = $this->getDefaultCommands();
        $this->hashGenerator = new CRC16HashGenerator();
    }

    /**
     * Returns the default map of supported commands with their handlers.
     *
     * @return array
     */
    protected function getDefaultCommands()
    {
        $keyIsFirstArgument = array($this, 'getKeyFromFirstArgument');

        return array(
            /* commands operating on the key space */
            'EXISTS'                => $keyIsFirstArgument,
            'DEL'                   => array($this, 'getKeyFromAllArguments'),
            'TYPE'                  => $keyIsFirstArgument,
            'EXPIRE'                => $keyIsFirstArgument,
            'EXPIREAT'              => $keyIsFirstArgument,
            'PERSIST'               => $keyIsFirstArgument,
            'PEXPIRE'               => $keyIsFirstArgument,
            'PEXPIREAT'             => $keyIsFirstArgument,
            'TTL'                   => $keyIsFirstArgument,
            'PTTL'                  => $keyIsFirstArgument,
            'SORT'                  => $keyIsFirstArgument, // TODO

            /* commands operating on string values */
            'APPEND'                => $keyIsFirstArgument,
            'DECR'                  => $keyIsFirstArgument,
            'DECRBY'                => $keyIsFirstArgument,
            'GET'                   => $keyIsFirstArgument,
            'GETBIT'                => $keyIsFirstArgument,
            'MGET'                  => array($this, 'getKeyFromAllArguments'),
            'SET'                   => $keyIsFirstArgument,
            'GETRANGE'              => $keyIsFirstArgument,
            'GETSET'                => $keyIsFirstArgument,
            'INCR'                  => $keyIsFirstArgument,
            'INCRBY'                => $keyIsFirstArgument,
            'SETBIT'                => $keyIsFirstArgument,
            'SETEX'                 => $keyIsFirstArgument,
            'MSET'                  => array($this, 'getKeyFromInterleavedArguments'),
            'MSETNX'                => array($this, 'getKeyFromInterleavedArguments'),
            'SETNX'                 => $keyIsFirstArgument,
            'SETRANGE'              => $keyIsFirstArgument,
            'STRLEN'                => $keyIsFirstArgument,
            'SUBSTR'                => $keyIsFirstArgument,
            'BITCOUNT'              => $keyIsFirstArgument,

            /* commands operating on lists */
            'LINSERT'               => $keyIsFirstArgument,
            'LINDEX'                => $keyIsFirstArgument,
            'LLEN'                  => $keyIsFirstArgument,
            'LPOP'                  => $keyIsFirstArgument,
            'RPOP'                  => $keyIsFirstArgument,
            'BLPOP'                 => array($this, 'getKeyFromBlockingListCommands'),
            'BRPOP'                 => array($this, 'getKeyFromBlockingListCommands'),
            'LPUSH'                 => $keyIsFirstArgument,
            'LPUSHX'                => $keyIsFirstArgument,
            'RPUSH'                 => $keyIsFirstArgument,
            'RPUSHX'                => $keyIsFirstArgument,
            'LRANGE'                => $keyIsFirstArgument,
            'LREM'                  => $keyIsFirstArgument,
            'LSET'                  => $keyIsFirstArgument,
            'LTRIM'                 => $keyIsFirstArgument,

            /* commands operating on sets */
            'SADD'                  => $keyIsFirstArgument,
            'SCARD'                 => $keyIsFirstArgument,
            'SISMEMBER'             => $keyIsFirstArgument,
            'SMEMBERS'              => $keyIsFirstArgument,
            'SPOP'                  => $keyIsFirstArgument,
            'SRANDMEMBER'           => $keyIsFirstArgument,
            'SREM'                  => $keyIsFirstArgument,

            /* commands operating on sorted sets */
            'ZADD'                  => $keyIsFirstArgument,
            'ZCARD'                 => $keyIsFirstArgument,
            'ZCOUNT'                => $keyIsFirstArgument,
            'ZINCRBY'               => $keyIsFirstArgument,
            'ZRANGE'                => $keyIsFirstArgument,
            'ZRANGEBYSCORE'         => $keyIsFirstArgument,
            'ZRANK'                 => $keyIsFirstArgument,
            'ZREM'                  => $keyIsFirstArgument,
            'ZREMRANGEBYRANK'       => $keyIsFirstArgument,
            'ZREMRANGEBYSCORE'      => $keyIsFirstArgument,
            'ZREVRANGE'             => $keyIsFirstArgument,
            'ZREVRANGEBYSCORE'      => $keyIsFirstArgument,
            'ZREVRANK'              => $keyIsFirstArgument,
            'ZSCORE'                => $keyIsFirstArgument,

            /* commands operating on hashes */
            'HDEL'                  => $keyIsFirstArgument,
            'HEXISTS'               => $keyIsFirstArgument,
            'HGET'                  => $keyIsFirstArgument,
            'HGETALL'               => $keyIsFirstArgument,
            'HMGET'                 => $keyIsFirstArgument,
            'HINCRBY'               => $keyIsFirstArgument,
            'HINCRBYFLOAT'          => $keyIsFirstArgument,
            'HKEYS'                 => $keyIsFirstArgument,
            'HLEN'                  => $keyIsFirstArgument,
            'HSET'                  => $keyIsFirstArgument,
            'HSETNX'                => $keyIsFirstArgument,
            'HVALS'                 => $keyIsFirstArgument,

            /* scripting */
            'EVAL'                  => array($this, 'getKeyFromScriptingCommands'),
            'EVALSHA'               => array($this, 'getKeyFromScriptingCommands'),
        );
    }

    /**
     * Returns the list of IDs for the supported commands.
     *
     * @return array
     */
    public function getSupportedCommands()
    {
        return array_keys($this->commands);
    }

    /**
     * Sets an handler for the specified command ID.
     *
     * The signature of the callback must have a single parameter
     * of type Predis\Command\CommandInterface.
     *
     * When the callback argument is omitted or NULL, the previously
     * associated handler for the specified command ID is removed.
     *
     * @param string $commandId The ID of the command to be handled.
     * @param mixed $callback A valid callable object or NULL.
     */
    public function setCommandHandler($commandId, $callback = null)
    {
        $commandId = strtoupper($commandId);

        if (!isset($callback)) {
            unset($this->commands[$commandId]);
            return;
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException("Callback must be a valid callable object or NULL");
        }

        $this->commands[$commandId] = $callback;
    }

    /**
     * Extracts the key from the first argument of a command instance.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromFirstArgument(CommandInterface $command)
    {
        return $command->getArgument(0);
    }

    /**
     * Extracts the key from a command that can accept multiple keys ensuring
     * that only one key is actually specified to comply with redis-cluster.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromAllArguments(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if (count($arguments) === 1) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from a command that can accept multiple keys ensuring
     * that only one key is actually specified to comply with redis-cluster.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromInterleavedArguments(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if (count($arguments) === 2) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from BLPOP and BRPOP commands ensuring that only one key
     * is actually specified to comply with redis-cluster.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromBlockingListCommands(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if (count($arguments) === 2) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from EVAL and EVALSHA commands.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromScriptingCommands(CommandInterface $command)
    {
        if ($command instanceof ScriptedCommand) {
            $keys = $command->getKeys();
        } else {
            $keys = array_slice($args = $command->getArguments(), 2, $args[1]);
        }

        if (count($keys) === 1) {
            return $keys[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHash(CommandInterface $command)
    {
        $hash = $command->getHash();

        if (!isset($hash) && isset($this->commands[$cmdID = $command->getId()])) {
            $key = call_user_func($this->commands[$cmdID], $command);

            if (isset($key)) {
                $hash = $this->hashGenerator->hash($key);
                $command->setHash($hash);
            }
        }

        return $hash;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyHash($key)
    {
        return $this->hashGenerator->hash($key);
    }
}
