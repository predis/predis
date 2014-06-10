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
use Predis\Command\ScriptedCommand;

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
        $getKeyFromFirstArgument = array($this, 'getKeyFromFirstArgument');
        $getKeyFromAllArguments = array($this, 'getKeyFromAllArguments');

        return array(
            /* commands operating on the key space */
            'EXISTS'                => $getKeyFromFirstArgument,
            'DEL'                   => $getKeyFromAllArguments,
            'TYPE'                  => $getKeyFromFirstArgument,
            'EXPIRE'                => $getKeyFromFirstArgument,
            'EXPIREAT'              => $getKeyFromFirstArgument,
            'PERSIST'               => $getKeyFromFirstArgument,
            'PEXPIRE'               => $getKeyFromFirstArgument,
            'PEXPIREAT'             => $getKeyFromFirstArgument,
            'TTL'                   => $getKeyFromFirstArgument,
            'PTTL'                  => $getKeyFromFirstArgument,
            'SORT'                  => $getKeyFromFirstArgument, // TODO

            /* commands operating on string values */
            'APPEND'                => $getKeyFromFirstArgument,
            'DECR'                  => $getKeyFromFirstArgument,
            'DECRBY'                => $getKeyFromFirstArgument,
            'GET'                   => $getKeyFromFirstArgument,
            'GETBIT'                => $getKeyFromFirstArgument,
            'MGET'                  => $getKeyFromAllArguments,
            'SET'                   => $getKeyFromFirstArgument,
            'GETRANGE'              => $getKeyFromFirstArgument,
            'GETSET'                => $getKeyFromFirstArgument,
            'INCR'                  => $getKeyFromFirstArgument,
            'INCRBY'                => $getKeyFromFirstArgument,
            'INCRBYFLOAT'           => $getKeyFromFirstArgument,
            'SETBIT'                => $getKeyFromFirstArgument,
            'SETEX'                 => $getKeyFromFirstArgument,
            'MSET'                  => array($this, 'getKeyFromInterleavedArguments'),
            'MSETNX'                => array($this, 'getKeyFromInterleavedArguments'),
            'SETNX'                 => $getKeyFromFirstArgument,
            'SETRANGE'              => $getKeyFromFirstArgument,
            'STRLEN'                => $getKeyFromFirstArgument,
            'SUBSTR'                => $getKeyFromFirstArgument,
            'BITCOUNT'              => $getKeyFromFirstArgument,

            /* commands operating on lists */
            'LINSERT'               => $getKeyFromFirstArgument,
            'LINDEX'                => $getKeyFromFirstArgument,
            'LLEN'                  => $getKeyFromFirstArgument,
            'LPOP'                  => $getKeyFromFirstArgument,
            'RPOP'                  => $getKeyFromFirstArgument,
            'BLPOP'                 => array($this, 'getKeyFromBlockingListCommands'),
            'BRPOP'                 => array($this, 'getKeyFromBlockingListCommands'),
            'LPUSH'                 => $getKeyFromFirstArgument,
            'LPUSHX'                => $getKeyFromFirstArgument,
            'RPUSH'                 => $getKeyFromFirstArgument,
            'RPUSHX'                => $getKeyFromFirstArgument,
            'LRANGE'                => $getKeyFromFirstArgument,
            'LREM'                  => $getKeyFromFirstArgument,
            'LSET'                  => $getKeyFromFirstArgument,
            'LTRIM'                 => $getKeyFromFirstArgument,

            /* commands operating on sets */
            'SADD'                  => $getKeyFromFirstArgument,
            'SCARD'                 => $getKeyFromFirstArgument,
            'SISMEMBER'             => $getKeyFromFirstArgument,
            'SMEMBERS'              => $getKeyFromFirstArgument,
            'SSCAN'                 => $getKeyFromFirstArgument,
            'SPOP'                  => $getKeyFromFirstArgument,
            'SRANDMEMBER'           => $getKeyFromFirstArgument,
            'SREM'                  => $getKeyFromFirstArgument,

            /* commands operating on sorted sets */
            'ZADD'                  => $getKeyFromFirstArgument,
            'ZCARD'                 => $getKeyFromFirstArgument,
            'ZCOUNT'                => $getKeyFromFirstArgument,
            'ZINCRBY'               => $getKeyFromFirstArgument,
            'ZRANGE'                => $getKeyFromFirstArgument,
            'ZRANGEBYSCORE'         => $getKeyFromFirstArgument,
            'ZRANK'                 => $getKeyFromFirstArgument,
            'ZREM'                  => $getKeyFromFirstArgument,
            'ZREMRANGEBYRANK'       => $getKeyFromFirstArgument,
            'ZREMRANGEBYSCORE'      => $getKeyFromFirstArgument,
            'ZREVRANGE'             => $getKeyFromFirstArgument,
            'ZREVRANGEBYSCORE'      => $getKeyFromFirstArgument,
            'ZREVRANK'              => $getKeyFromFirstArgument,
            'ZSCORE'                => $getKeyFromFirstArgument,
            'ZSCAN'                 => $getKeyFromFirstArgument,
            'ZLEXCOUNT'             => $getKeyFromFirstArgument,
            'ZRANGEBYLEX'           => $getKeyFromFirstArgument,
            'ZREMRANGEBYLEX'        => $getKeyFromFirstArgument,

            /* commands operating on hashes */
            'HDEL'                  => $getKeyFromFirstArgument,
            'HEXISTS'               => $getKeyFromFirstArgument,
            'HGET'                  => $getKeyFromFirstArgument,
            'HGETALL'               => $getKeyFromFirstArgument,
            'HMGET'                 => $getKeyFromFirstArgument,
            'HMSET'                 => $getKeyFromFirstArgument,
            'HINCRBY'               => $getKeyFromFirstArgument,
            'HINCRBYFLOAT'          => $getKeyFromFirstArgument,
            'HKEYS'                 => $getKeyFromFirstArgument,
            'HLEN'                  => $getKeyFromFirstArgument,
            'HSET'                  => $getKeyFromFirstArgument,
            'HSETNX'                => $getKeyFromFirstArgument,
            'HVALS'                 => $getKeyFromFirstArgument,
            'HSCAN'                 => $getKeyFromFirstArgument,

            /* commands operating on HyperLogLog */
            'PFADD'                 => $getKeyFromFirstArgument,
            'PFCOUNT'               => $getKeyFromAllArguments,
            'PFMERGE'               => $getKeyFromAllArguments,

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
     * @param mixed  $callback  A valid callable object or NULL.
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
     * @param  CommandInterface $command Command instance.
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
     * @param  CommandInterface $command Command instance.
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
     * @param  CommandInterface $command Command instance.
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
     * @param  CommandInterface $command Command instance.
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
     * @param  CommandInterface $command Command instance.
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
        $key = $this->extractKeyTag($key);
        $hash = $this->hashGenerator->hash($key);

        return $hash;
    }

    /**
     * Returns only the hashable part of a key (delimited by "{...}"), or the
     * whole key if a key tag is not found in the string.
     *
     * @param  string $key A key.
     * @return string
     */
    protected function extractKeyTag($key)
    {
        if (false !== $start = strpos($key, '{')) {
            if (false !== ($end = strpos($key, '}', $start)) && $end !== ++$start) {
                $key = substr($key, $start, $end - $start);
            }
        }

        return $key;
    }
}
