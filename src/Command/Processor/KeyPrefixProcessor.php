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

use InvalidArgumentException;
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
        $this->commands = array(
            /* ---------------- Redis 1.2 ---------------- */
            'EXISTS'                    => 'self::first',
            'DEL'                       => 'self::all',
            'TYPE'                      => 'self::first',
            'KEYS'                      => 'self::first',
            'RENAME'                    => 'self::all',
            'RENAMENX'                  => 'self::all',
            'EXPIRE'                    => 'self::first',
            'EXPIREAT'                  => 'self::first',
            'TTL'                       => 'self::first',
            'MOVE'                      => 'self::first',
            'SORT'                      => 'self::sort',
            'DUMP'                      => 'self::first',
            'RESTORE'                   => 'self::first',
            'SET'                       => 'self::first',
            'SETNX'                     => 'self::first',
            'MSET'                      => 'self::interleaved',
            'MSETNX'                    => 'self::interleaved',
            'GET'                       => 'self::first',
            'MGET'                      => 'self::all',
            'GETSET'                    => 'self::first',
            'INCR'                      => 'self::first',
            'INCRBY'                    => 'self::first',
            'DECR'                      => 'self::first',
            'DECRBY'                    => 'self::first',
            'RPUSH'                     => 'self::first',
            'LPUSH'                     => 'self::first',
            'LLEN'                      => 'self::first',
            'LRANGE'                    => 'self::first',
            'LTRIM'                     => 'self::first',
            'LINDEX'                    => 'self::first',
            'LSET'                      => 'self::first',
            'LREM'                      => 'self::first',
            'LPOP'                      => 'self::first',
            'RPOP'                      => 'self::first',
            'RPOPLPUSH'                 => 'self::all',
            'SADD'                      => 'self::first',
            'SREM'                      => 'self::first',
            'SPOP'                      => 'self::first',
            'SMOVE'                     => 'self::skipLast',
            'SCARD'                     => 'self::first',
            'SISMEMBER'                 => 'self::first',
            'SINTER'                    => 'self::all',
            'SINTERSTORE'               => 'self::all',
            'SUNION'                    => 'self::all',
            'SUNIONSTORE'               => 'self::all',
            'SDIFF'                     => 'self::all',
            'SDIFFSTORE'                => 'self::all',
            'SMEMBERS'                  => 'self::first',
            'SRANDMEMBER'               => 'self::first',
            'ZADD'                      => 'self::first',
            'ZINCRBY'                   => 'self::first',
            'ZREM'                      => 'self::first',
            'ZRANGE'                    => 'self::first',
            'ZREVRANGE'                 => 'self::first',
            'ZRANGEBYSCORE'             => 'self::first',
            'ZCARD'                     => 'self::first',
            'ZSCORE'                    => 'self::first',
            'ZREMRANGEBYSCORE'          => 'self::first',
            /* ---------------- Redis 2.0 ---------------- */
            'SETEX'                     => 'self::first',
            'APPEND'                    => 'self::first',
            'SUBSTR'                    => 'self::first',
            'BLPOP'                     => 'self::skipLast',
            'BRPOP'                     => 'self::skipLast',
            'ZUNIONSTORE'               => 'self::zsetStore',
            'ZINTERSTORE'               => 'self::zsetStore',
            'ZCOUNT'                    => 'self::first',
            'ZRANK'                     => 'self::first',
            'ZREVRANK'                  => 'self::first',
            'ZREMRANGEBYRANK'           => 'self::first',
            'HSET'                      => 'self::first',
            'HSETNX'                    => 'self::first',
            'HMSET'                     => 'self::first',
            'HINCRBY'                   => 'self::first',
            'HGET'                      => 'self::first',
            'HMGET'                     => 'self::first',
            'HDEL'                      => 'self::first',
            'HEXISTS'                   => 'self::first',
            'HLEN'                      => 'self::first',
            'HKEYS'                     => 'self::first',
            'HVALS'                     => 'self::first',
            'HGETALL'                   => 'self::first',
            'SUBSCRIBE'                 => 'self::all',
            'UNSUBSCRIBE'               => 'self::all',
            'PSUBSCRIBE'                => 'self::all',
            'PUNSUBSCRIBE'              => 'self::all',
            'PUBLISH'                   => 'self::first',
            /* ---------------- Redis 2.2 ---------------- */
            'PERSIST'                   => 'self::first',
            'STRLEN'                    => 'self::first',
            'SETRANGE'                  => 'self::first',
            'GETRANGE'                  => 'self::first',
            'SETBIT'                    => 'self::first',
            'GETBIT'                    => 'self::first',
            'RPUSHX'                    => 'self::first',
            'LPUSHX'                    => 'self::first',
            'LINSERT'                   => 'self::first',
            'BRPOPLPUSH'                => 'self::skipLast',
            'ZREVRANGEBYSCORE'          => 'self::first',
            'WATCH'                     => 'self::all',
            /* ---------------- Redis 2.6 ---------------- */
            'PTTL'                      => 'self::first',
            'PEXPIRE'                   => 'self::first',
            'PEXPIREAT'                 => 'self::first',
            'PSETEX'                    => 'self::first',
            'INCRBYFLOAT'               => 'self::first',
            'BITOP'                     => 'self::skipFirst',
            'BITCOUNT'                  => 'self::first',
            'HINCRBYFLOAT'              => 'self::first',
            'EVAL'                      => 'self::evalKeys',
            'EVALSHA'                   => 'self::evalKeys',
            /* ---------------- Redis 2.8 ---------------- */
            'SSCAN'                     => 'self::first',
            'ZSCAN'                     => 'self::first',
            'HSCAN'                     => 'self::first',
            'PFADD'                     => 'self::first',
            'PFCOUNT'                   => 'self::all',
            'PFMERGE'                   => 'self::all',
            'ZLEXCOUNT'                 => 'self::first',
            'ZRANGEBYLEX'               => 'self::first',
            'ZREMRANGEBYLEX'            => 'self::first',
            'ZREVRANGEBYLEX'            => 'self::first',
        );
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
            call_user_func($this->commands[$commandID], $command, $this->prefix);
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
            throw new InvalidArgumentException(
                "Callback must be a valid callable object or NULL"
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

            for ($i = 1; $i < $length; $i++) {
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

            for ($i = 0; $i < $length - 1; $i++) {
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
                for ($i = 1; $i < $count; $i++) {
                    switch ($arguments[$i]) {
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
            for ($i = 2; $i < $arguments[1] + 2; $i++) {
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

            for ($i = 2; $i < $length; $i++) {
                $arguments[$i] = "$prefix{$arguments[$i]}";
            }

            $command->setRawArguments($arguments);
        }
    }
}
