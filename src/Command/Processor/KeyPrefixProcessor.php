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

namespace Predis\Command\Processor;

use InvalidArgumentException;
use Predis\Command\CommandInterface;
use Predis\Command\PrefixableCommandInterface;

/**
 * Command processor capable of prefixing keys stored in the arguments of Redis
 * commands supported.
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

        $prefixFirst = static::class . '::first';
        $prefixAll = static::class . '::all';
        $prefixInterleaved = static::class . '::interleaved';
        $prefixSkipFirst = static::class . '::skipFirst';
        $prefixSkipLast = static::class . '::skipLast';
        $prefixSort = static::class . '::sort';
        $prefixEvalKeys = static::class . '::evalKeys';
        $prefixZsetStore = static::class . '::zsetStore';
        $prefixMigrate = static::class . '::migrate';
        $prefixGeoradius = static::class . '::georadius';

        $this->commands = [
            /* ---------------- Redis 1.2 ---------------- */
            'EXISTS' => $prefixAll,
            'DEL' => $prefixAll,
            'TYPE' => $prefixFirst,
            'KEYS' => $prefixFirst,
            'RENAME' => $prefixAll,
            'RENAMENX' => $prefixAll,
            'EXPIRE' => $prefixFirst,
            'EXPIREAT' => $prefixFirst,
            'TTL' => $prefixFirst,
            'MOVE' => $prefixFirst,
            'SORT' => $prefixSort,
            'DUMP' => $prefixFirst,
            'RESTORE' => $prefixFirst,
            'SET' => $prefixFirst,
            'SETNX' => $prefixFirst,
            'MSET' => $prefixInterleaved,
            'MSETNX' => $prefixInterleaved,
            'GET' => $prefixFirst,
            'MGET' => $prefixAll,
            'GETSET' => $prefixFirst,
            'INCR' => $prefixFirst,
            'INCRBY' => $prefixFirst,
            'DECR' => $prefixFirst,
            'DECRBY' => $prefixFirst,
            'RPUSH' => $prefixFirst,
            'LPUSH' => $prefixFirst,
            'LLEN' => $prefixFirst,
            'LRANGE' => $prefixFirst,
            'LTRIM' => $prefixFirst,
            'LINDEX' => $prefixFirst,
            'LSET' => $prefixFirst,
            'LREM' => $prefixFirst,
            'LPOP' => $prefixFirst,
            'RPOP' => $prefixFirst,
            'RPOPLPUSH' => $prefixAll,
            'SADD' => $prefixFirst,
            'SREM' => $prefixFirst,
            'SPOP' => $prefixFirst,
            'SMOVE' => $prefixSkipLast,
            'SCARD' => $prefixFirst,
            'SISMEMBER' => $prefixFirst,
            'SINTER' => $prefixAll,
            'SINTERSTORE' => $prefixAll,
            'SUNION' => $prefixAll,
            'SUNIONSTORE' => $prefixAll,
            'SDIFF' => $prefixAll,
            'SDIFFSTORE' => $prefixAll,
            'SMEMBERS' => $prefixFirst,
            'SRANDMEMBER' => $prefixFirst,
            'ZADD' => $prefixFirst,
            'ZINCRBY' => $prefixFirst,
            'ZREM' => $prefixFirst,
            'ZRANGE' => $prefixFirst,
            'ZREVRANGE' => $prefixFirst,
            'ZRANGEBYSCORE' => $prefixFirst,
            'ZCARD' => $prefixFirst,
            'ZSCORE' => $prefixFirst,
            'ZREMRANGEBYSCORE' => $prefixFirst,
            /* ---------------- Redis 2.0 ---------------- */
            'SETEX' => $prefixFirst,
            'APPEND' => $prefixFirst,
            'SUBSTR' => $prefixFirst,
            'BLPOP' => $prefixSkipLast,
            'BRPOP' => $prefixSkipLast,
            'ZUNIONSTORE' => $prefixZsetStore,
            'ZINTERSTORE' => $prefixZsetStore,
            'ZCOUNT' => $prefixFirst,
            'ZRANK' => $prefixFirst,
            'ZREVRANK' => $prefixFirst,
            'ZREMRANGEBYRANK' => $prefixFirst,
            'HSET' => $prefixFirst,
            'HSETNX' => $prefixFirst,
            'HMSET' => $prefixFirst,
            'HINCRBY' => $prefixFirst,
            'HGET' => $prefixFirst,
            'HMGET' => $prefixFirst,
            'HDEL' => $prefixFirst,
            'HEXISTS' => $prefixFirst,
            'HLEN' => $prefixFirst,
            'HKEYS' => $prefixFirst,
            'HVALS' => $prefixFirst,
            'HGETALL' => $prefixFirst,
            'SUBSCRIBE' => $prefixAll,
            'UNSUBSCRIBE' => $prefixAll,
            'PSUBSCRIBE' => $prefixAll,
            'PUNSUBSCRIBE' => $prefixAll,
            'PUBLISH' => $prefixFirst,
            /* ---------------- Redis 2.2 ---------------- */
            'PERSIST' => $prefixFirst,
            'STRLEN' => $prefixFirst,
            'SETRANGE' => $prefixFirst,
            'GETRANGE' => $prefixFirst,
            'SETBIT' => $prefixFirst,
            'GETBIT' => $prefixFirst,
            'RPUSHX' => $prefixFirst,
            'LPUSHX' => $prefixFirst,
            'LINSERT' => $prefixFirst,
            'BRPOPLPUSH' => $prefixSkipLast,
            'ZREVRANGEBYSCORE' => $prefixFirst,
            'WATCH' => $prefixAll,
            /* ---------------- Redis 2.6 ---------------- */
            'PTTL' => $prefixFirst,
            'PEXPIRE' => $prefixFirst,
            'PEXPIREAT' => $prefixFirst,
            'PSETEX' => $prefixFirst,
            'INCRBYFLOAT' => $prefixFirst,
            'BITOP' => $prefixSkipFirst,
            'BITCOUNT' => $prefixFirst,
            'HINCRBYFLOAT' => $prefixFirst,
            'EVAL' => $prefixEvalKeys,
            'EVALSHA' => $prefixEvalKeys,
            'MIGRATE' => $prefixMigrate,
            /* ---------------- Redis 2.8 ---------------- */
            'SSCAN' => $prefixFirst,
            'ZSCAN' => $prefixFirst,
            'HSCAN' => $prefixFirst,
            'PFADD' => $prefixFirst,
            'PFCOUNT' => $prefixAll,
            'PFMERGE' => $prefixAll,
            'ZLEXCOUNT' => $prefixFirst,
            'ZRANGEBYLEX' => $prefixFirst,
            'ZREMRANGEBYLEX' => $prefixFirst,
            'ZREVRANGEBYLEX' => $prefixFirst,
            'BITPOS' => $prefixFirst,
            /* ---------------- Redis 3.2 ---------------- */
            'HSTRLEN' => $prefixFirst,
            'BITFIELD' => $prefixFirst,
            'GEOADD' => $prefixFirst,
            'GEOHASH' => $prefixFirst,
            'GEOPOS' => $prefixFirst,
            'GEODIST' => $prefixFirst,
            'GEORADIUS' => $prefixGeoradius,
            'GEORADIUSBYMEMBER' => $prefixGeoradius,
            /* ---------------- Redis 5.0 ---------------- */
            'XADD' => $prefixFirst,
            'XRANGE' => $prefixFirst,
            'XREVRANGE' => $prefixFirst,
            'XDEL' => $prefixFirst,
            'XLEN' => $prefixFirst,
            'XACK' => $prefixFirst,
            'XTRIM' => $prefixFirst,

            /* ---------------- Redis 6.2 ---------------- */
            'GETDEL' => $prefixFirst,
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
     * @throws InvalidArgumentException
     */
    public function setCommandHandler($commandID, $callback = null)
    {
        $commandID = strtoupper($commandID);

        if (!isset($callback)) {
            unset($this->commands[$commandID]);

            return;
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException(
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

                        case 'LIMIT':
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
