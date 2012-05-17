<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Hash;

use Predis\Command\CommandInterface;
use Predis\Distribution\HashGeneratorInterface;

/**
 * Default class used by Predis for client-side sharding to calculate
 * hashes out of keys of supported commands.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class CommandHashStrategy implements CommandHashStrategyInterface
{
    private $commands;

    /**
     *
     */
    public function __construct()
    {
        $this->commands = $this->getDefaultCommands();
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
            'BITOP'                 => array($this, 'getKeyFromBitOp'),
            'BITCOUNT'              => $keyIsFirstArgument,

            /* commands operating on lists */
            'LINSERT'               => $keyIsFirstArgument,
            'LINDEX'                => $keyIsFirstArgument,
            'LLEN'                  => $keyIsFirstArgument,
            'LPOP'                  => $keyIsFirstArgument,
            'RPOP'                  => $keyIsFirstArgument,
            'RPOPLPUSH'             => array($this, 'getKeyFromAllArguments'),
            'BLPOP'                 => array($this, 'getKeyFromBlockingListCommands'),
            'BRPOP'                 => array($this, 'getKeyFromBlockingListCommands'),
            'BRPOPLPUSH'            => array($this, 'getKeyFromBlockingListCommands'),
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
            'SDIFF'                 => array($this, 'getKeyFromAllArguments'),
            'SDIFFSTORE'            => array($this, 'getKeyFromAllArguments'),
            'SINTER'                => array($this, 'getKeyFromAllArguments'),
            'SINTERSTORE'           => array($this, 'getKeyFromAllArguments'),
            'SUNION'                => array($this, 'getKeyFromAllArguments'),
            'SUNIONSTORE'           => array($this, 'getKeyFromAllArguments'),
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
            'ZINTERSTORE'           => array($this, 'getKeyFromZsetAggregationCommands'),
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
            'ZUNIONSTORE'           => array($this, 'getKeyFromZsetAggregationCommands'),

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
     * Extracts the key from a command with multiple keys only when all keys
     * in the arguments array produce the same hash.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromAllArguments(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if ($this->checkSameHashForKeys($arguments)) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from a command with multiple keys only when all keys
     * in the arguments array produce the same hash.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromInterleavedArguments(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        $keys = array();

        for ($i = 0; $i < count($arguments); $i += 2) {
            $keys[] = $arguments[$i];
        }

        if ($this->checkSameHashForKeys($keys)) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from BLPOP and BRPOP commands.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromBlockingListCommands(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if ($this->checkSameHashForKeys(array_slice($arguments, 0, count($arguments) - 1))) {
            return $arguments[0];
        }
    }

    /**
     * Extracts the key from BITOP command.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromBitOp(CommandInterface $command)
    {
        $arguments = $command->getArguments();

        if ($this->checkSameHashForKeys(array_slice($arguments, 1, count($arguments)))) {
            return $arguments[1];
        }
    }

    /**
     * Extracts the key from ZINTERSTORE and ZUNIONSTORE commands.
     *
     * @param CommandInterface $command Command instance.
     * @return string
     */
    protected function getKeyFromZsetAggregationCommands(CommandInterface $command)
    {
        $arguments = $command->getArguments();
        $keys = array_merge(array($arguments[0]), array_slice($arguments, 2, $arguments[1]));

        if ($this->checkSameHashForKeys($keys)) {
            return $arguments[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHash(HashGeneratorInterface $hasher, CommandInterface $command)
    {
        if (isset($this->commands[$cmdID = $command->getId()])) {
            if ($key = call_user_func($this->commands[$cmdID], $command)) {
                return $this->getKeyHash($hasher, $key);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyHash(HashGeneratorInterface $hasher, $key)
    {
        $key = $this->extractKeyTag($key);
        $hash = $hasher->hash($key);

        return $hash;
    }

    /**
     * Checks if the specified array of keys will generate the same hash.
     *
     * @param array $keys Array of keys.
     * @return Boolean
     */
    protected function checkSameHashForKeys(Array $keys)
    {
        if (($count = count($keys)) === 0) {
            return false;
        }

        $currentKey = $this->extractKeyTag($keys[0]);

        for ($i = 1; $i < $count; $i++) {
            $nextKey = $this->extractKeyTag($keys[$i]);
            if ($currentKey !== $nextKey) {
                return false;
            }
            $currentKey = $nextKey;
        }

        return true;
    }

    /**
     * Returns only the hashable part of a key (delimited by "{...}"), or the
     * whole key if a key tag is not found in the string.
     *
     * @param string $key A key.
     * @return string
     */
    protected function extractKeyTag($key)
    {
        $start = strpos($key, '{');
        if ($start !== false) {
            $end = strpos($key, '}', $start);
            if ($end !== false) {
                $key = substr($key, ++$start, $end - $start);
            }
        }

        return $key;
    }
}
