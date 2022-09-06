<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Processor;

use Predis\Command\CommandInterface;
use Predis\Command\PrefixableCommandInterface;

/**
 * Command processor capable of prefixing keys stored in the arguments of Redis
 * commands supported.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class KeyPrefixProcessor implements ProcessorInterface
{
    private $prefix;
    private $commands;

    /**
     * @param string $prefix Prefix for the keys.
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
        $this->commands = [
            /* ---------------- Redis 1.2 ---------------- */
            'EXISTS' => static::class . '::all',
            'DEL' => static::class . '::all',
            'TYPE' => static::class . '::first',
            'KEYS' => static::class . '::first',
            'RENAME' => static::class . '::all',
            'RENAMENX' => static::class . '::all',
            'EXPIRE' => static::class . '::first',
            'EXPIREAT' => static::class . '::first',
            'TTL' => static::class . '::first',
            'MOVE' => static::class . '::first',
            'SORT' => static::class . '::sort',
            'DUMP' => static::class . '::first',
            'RESTORE' => static::class . '::first',
            'SET' => static::class . '::first',
            'SETNX' => static::class . '::first',
            'MSET' => static::class . '::interleaved',
            'MSETNX' => static::class . '::interleaved',
            'GET' => static::class . '::first',
            'MGET' => static::class . '::all',
            'GETSET' => static::class . '::first',
            'INCR' => static::class . '::first',
            'INCRBY' => static::class . '::first',
            'DECR' => static::class . '::first',
            'DECRBY' => static::class . '::first',
            'RPUSH' => static::class . '::first',
            'LPUSH' => static::class . '::first',
            'LLEN' => static::class . '::first',
            'LRANGE' => static::class . '::first',
            'LTRIM' => static::class . '::first',
            'LINDEX' => static::class . '::first',
            'LSET' => static::class . '::first',
            'LREM' => static::class . '::first',
            'LPOP' => static::class . '::first',
            'RPOP' => static::class . '::first',
            'RPOPLPUSH' => static::class . '::all',
            'SADD' => static::class . '::first',
            'SREM' => static::class . '::first',
            'SPOP' => static::class . '::first',
            'SMOVE' => static::class . '::skipLast',
            'SCARD' => static::class . '::first',
            'SISMEMBER' => static::class . '::first',
            'SINTER' => static::class . '::all',
            'SINTERSTORE' => static::class . '::all',
            'SUNION' => static::class . '::all',
            'SUNIONSTORE' => static::class . '::all',
            'SDIFF' => static::class . '::all',
            'SDIFFSTORE' => static::class . '::all',
            'SMEMBERS' => static::class . '::first',
            'SRANDMEMBER' => static::class . '::first',
            'ZADD' => static::class . '::first',
            'ZINCRBY' => static::class . '::first',
            'ZREM' => static::class . '::first',
            'ZRANGE' => static::class . '::first',
            'ZREVRANGE' => static::class . '::first',
            'ZRANGEBYSCORE' => static::class . '::first',
            'ZCARD' => static::class . '::first',
            'ZSCORE' => static::class . '::first',
            'ZREMRANGEBYSCORE' => static::class . '::first',
            /* ---------------- Redis 2.0 ---------------- */
            'SETEX' => static::class . '::first',
            'APPEND' => static::class . '::first',
            'SUBSTR' => static::class . '::first',
            'BLPOP' => static::class . '::skipLast',
            'BRPOP' => static::class . '::skipLast',
            'ZUNIONSTORE' => static::class . '::zsetStore',
            'ZINTERSTORE' => static::class . '::zsetStore',
            'ZCOUNT' => static::class . '::first',
            'ZRANK' => static::class . '::first',
            'ZREVRANK' => static::class . '::first',
            'ZREMRANGEBYRANK' => static::class . '::first',
            'HSET' => static::class . '::first',
            'HSETNX' => static::class . '::first',
            'HMSET' => static::class . '::first',
            'HINCRBY' => static::class . '::first',
            'HGET' => static::class . '::first',
            'HMGET' => static::class . '::first',
            'HDEL' => static::class . '::first',
            'HEXISTS' => static::class . '::first',
            'HLEN' => static::class . '::first',
            'HKEYS' => static::class . '::first',
            'HVALS' => static::class . '::first',
            'HGETALL' => static::class . '::first',
            'SUBSCRIBE' => static::class . '::all',
            'UNSUBSCRIBE' => static::class . '::all',
            'PSUBSCRIBE' => static::class . '::all',
            'PUNSUBSCRIBE' => static::class . '::all',
            'PUBLISH' => static::class . '::first',
            /* ---------------- Redis 2.2 ---------------- */
            'PERSIST' => static::class . '::first',
            'STRLEN' => static::class . '::first',
            'SETRANGE' => static::class . '::first',
            'GETRANGE' => static::class . '::first',
            'SETBIT' => static::class . '::first',
            'GETBIT' => static::class . '::first',
            'RPUSHX' => static::class . '::first',
            'LPUSHX' => static::class . '::first',
            'LINSERT' => static::class . '::first',
            'BRPOPLPUSH' => static::class . '::skipLast',
            'ZREVRANGEBYSCORE' => static::class . '::first',
            'WATCH' => static::class . '::all',
            /* ---------------- Redis 2.6 ---------------- */
            'PTTL' => static::class . '::first',
            'PEXPIRE' => static::class . '::first',
            'PEXPIREAT' => static::class . '::first',
            'PSETEX' => static::class . '::first',
            'INCRBYFLOAT' => static::class . '::first',
            'BITOP' => static::class . '::skipFirst',
            'BITCOUNT' => static::class . '::first',
            'HINCRBYFLOAT' => static::class . '::first',
            'EVAL' => static::class . '::evalKeys',
            'EVALSHA' => static::class . '::evalKeys',
            'MIGRATE' => static::class . '::migrate',
            /* ---------------- Redis 2.8 ---------------- */
            'SSCAN' => static::class . '::first',
            'ZSCAN' => static::class . '::first',
            'HSCAN' => static::class . '::first',
            'PFADD' => static::class . '::first',
            'PFCOUNT' => static::class . '::all',
            'PFMERGE' => static::class . '::all',
            'ZLEXCOUNT' => static::class . '::first',
            'ZRANGEBYLEX' => static::class . '::first',
            'ZREMRANGEBYLEX' => static::class . '::first',
            'ZREVRANGEBYLEX' => static::class . '::first',
            'BITPOS' => static::class . '::first',
            /* ---------------- Redis 3.2 ---------------- */
            'HSTRLEN' => static::class . '::first',
            'BITFIELD' => static::class . '::first',
            'GEOADD' => static::class . '::first',
            'GEOHASH' => static::class . '::first',
            'GEOPOS' => static::class . '::first',
            'GEODIST' => static::class . '::first',
            'GEORADIUS' => static::class . '::georadius',
            'GEORADIUSBYMEMBER' => static::class . '::georadius',
        ];
    }

    /**
     * Sets a prefix that is applied to all the keys.
     *
     * @param string $prefix Prefix for the keys.
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Gets the current prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function process(CommandInterface $command)
    {
        if ($command instanceof PrefixableCommandInterface) {
            $command->prefixKeys($this->prefix);
        } elseif (isset($this->commands[$commandID = strtoupper($command->getId())])) {
            $this->commands[$commandID]($command, $this->prefix);
        }
    }

    /**
     * Sets an handler for the specified command ID.
     *
     * The callback signature must have 2 parameters of the following types:
     *
     *   - Predis\Command\CommandInterface (command instance)
     *   - String (prefix)
     *
     * When the callback argument is omitted or NULL, the previously
     * associated handler for the specified command ID is removed.
     *
     * @param string $commandID The ID of the command to be handled.
     * @param mixed  $callback  A valid callable object or NULL.
     *
     * @throws \InvalidArgumentException
     */
    public function setCommandHandler($commandID, $callback = null)
    {
        $commandID = strtoupper($commandID);

        if (!isset($callback)) {
            unset($this->commands[$commandID]);

            return;
        }

        if (!is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Callback must be a valid callable object or NULL'
            );
        }

        $this->commands[$commandID] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getPrefix();
    }

    /**
     * Applies the specified prefix only the first argument.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function first(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";
            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function all(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            foreach ($arguments as &$key) {
                $key = "$prefix$key";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix only to even arguments in the list.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function interleaved(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length; $i += 2) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments but the first one.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function skipFirst(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $length = count($arguments);

            for ($i = 1; $i < $length; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to all the arguments but the last one.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function skipLast(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $length = count($arguments);

            for ($i = 0; $i < $length - 1; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the keys of a SORT command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function sort(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";

            if (($count = count($arguments)) > 1) {
                for ($i = 1; $i < $count; ++$i) {
                    switch (strtoupper($arguments[$i])) {
                        case 'BY':
                        case 'STORE':
                            $arguments[$i] = "$prefix{$arguments[++$i]}";
                            break;

                        case 'GET':
                            $value = $arguments[++$i];
                            if ($value !== '#') {
                                $arguments[$i] = "$prefix$value";
                            }
                            break;

                        case 'LIMIT';
                            $i += 2;
                            break;
                    }
                }
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the keys of an EVAL-based command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function evalKeys(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            for ($i = 2; $i < $arguments[1] + 2; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the keys of Z[INTERSECTION|UNION]STORE.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function zsetStore(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";
            $length = ((int) $arguments[1]) + 2;

            for ($i = 2; $i < $length; ++$i) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the key of a MIGRATE command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function migrate(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[2] = "$prefix{$arguments[2]}";
            $command->setRawArguments($arguments);
        }
    }

    /**
     * Applies the specified prefix to the key of a GEORADIUS command.
     *
     * @param CommandInterface $command Command instance.
     * @param string           $prefix  Prefix string.
     */
    public static function georadius(CommandInterface $command, $prefix)
    {
        if ($arguments = $command->getArguments()) {
            $arguments[0] = "$prefix{$arguments[0]}";
            $startIndex = $command->getId() === 'GEORADIUS' ? 5 : 4;

            if (($count = count($arguments)) > $startIndex) {
                for ($i = $startIndex; $i < $count; ++$i) {
                    switch (strtoupper($arguments[$i])) {
                        case 'STORE':
                        case 'STOREDIST':
                            $arguments[$i] = "$prefix{$arguments[++$i]}";
                            break;

                    }
                }
            }

            $command->setRawArguments($arguments);
        }
    }
}
