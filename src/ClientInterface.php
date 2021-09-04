<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use Predis\Command\CommandInterface;
use Predis\Command\FactoryInterface;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Response\Status;

/**
 * Interface defining a client able to execute commands against Redis.
 *
 * All the commands exposed by the client generally have the same signature as
 * described by the Redis documentation, but some of them offer an additional
 * and more friendly interface to ease programming which is described in the
 * following list of methods:
 *
 * @method int         del(array|string $keys)
 * @method string|null dump($key)
 * @method int         exists($key)
 * @method int         expire($key, $seconds)
 * @method int         expireat($key, $timestamp)
 * @method array       keys($pattern)
 * @method int         move($key, $db)
 * @method mixed       object($subcommand, $key)
 * @method int         persist($key)
 * @method int         pexpire($key, $milliseconds)
 * @method int         pexpireat($key, $timestamp)
 * @method int         pttl($key)
 * @method string|null randomkey()
 * @method mixed       rename($key, $target)
 * @method int         renamenx($key, $target)
 * @method array       scan($cursor, array $options = null)
 * @method array       sort($key, array $options = null)
 * @method int         ttl($key)
 * @method mixed       type($key)
 * @method int         append($key, $value)
 * @method int         bitcount($key, $start = null, $end = null)
 * @method int         bitop($operation, $destkey, $key)
 * @method array|null  bitfield($key, $subcommand, ...$subcommandArg)
 * @method int         bitpos($key, $bit, $start = null, $end = null)
 * @method int         decr($key)
 * @method int         decrby($key, $decrement)
 * @method string|null get($key)
 * @method int         getbit($key, $offset)
 * @method string      getrange($key, $start, $end)
 * @method string|null getset($key, $value)
 * @method int         incr($key)
 * @method int         incrby($key, $increment)
 * @method string      incrbyfloat($key, $increment)
 * @method array       mget(array $keys)
 * @method mixed       mset(array $dictionary)
 * @method int         msetnx(array $dictionary)
 * @method Status      psetex($key, $milliseconds, $value)
 * @method Status      set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method int         setbit($key, $offset, $value)
 * @method Status      setex($key, $seconds, $value)
 * @method int         setnx($key, $value)
 * @method int         setrange($key, $offset, $value)
 * @method int         strlen($key)
 * @method int         hdel($key, array $fields)
 * @method int         hexists($key, $field)
 * @method string|null hget($key, $field)
 * @method array       hgetall($key)
 * @method int         hincrby($key, $field, $increment)
 * @method string      hincrbyfloat($key, $field, $increment)
 * @method array       hkeys($key)
 * @method int         hlen($key)
 * @method array       hmget($key, array $fields)
 * @method mixed       hmset($key, array $dictionary)
 * @method array       hscan($key, $cursor, array $options = null)
 * @method int         hset($key, $field, $value)
 * @method int         hsetnx($key, $field, $value)
 * @method array       hvals($key)
 * @method int         hstrlen($key, $field)
 * @method array|null  blpop(array|string $keys, $timeout)
 * @method array|null  brpop(array|string $keys, $timeout)
 * @method string|null brpoplpush($source, $destination, $timeout)
 * @method string|null lindex($key, $index)
 * @method int         linsert($key, $whence, $pivot, $value)
 * @method int         llen($key)
 * @method string|null lpop($key)
 * @method int         lpush($key, array $values)
 * @method int         lpushx($key, array $values)
 * @method array       lrange($key, $start, $stop)
 * @method int         lrem($key, $count, $value)
 * @method mixed       lset($key, $index, $value)
 * @method mixed       ltrim($key, $start, $stop)
 * @method string|null rpop($key)
 * @method string|null rpoplpush($source, $destination)
 * @method int         rpush($key, array $values)
 * @method int         rpushx($key, array $values)
 * @method int         sadd($key, array $members)
 * @method int         scard($key)
 * @method array       sdiff(array|string $keys)
 * @method int         sdiffstore($destination, array|string $keys)
 * @method array       sinter(array|string $keys)
 * @method int         sinterstore($destination, array|string $keys)
 * @method int         sismember($key, $member)
 * @method array       smembers($key)
 * @method int         smove($source, $destination, $member)
 * @method string|null spop($key, $count = null)
 * @method string|null srandmember($key, $count = null)
 * @method int         srem($key, $member)
 * @method array       sscan($key, $cursor, array $options = null)
 * @method array       sunion(array|string $keys)
 * @method int         sunionstore($destination, array|string $keys)
 * @method int         zadd($key, array $membersAndScoresDictionary)
 * @method int         zcard($key)
 * @method string      zcount($key, $min, $max)
 * @method string      zincrby($key, $increment, $member)
 * @method int         zinterstore($destination, array|string $keys, array $options = null)
 * @method array       zrange($key, $start, $stop, array $options = null)
 * @method array       zrangebyscore($key, $min, $max, array $options = null)
 * @method int|null    zrank($key, $member)
 * @method int         zrem($key, $member)
 * @method int         zremrangebyrank($key, $start, $stop)
 * @method int         zremrangebyscore($key, $min, $max)
 * @method array       zrevrange($key, $start, $stop, array $options = null)
 * @method array       zrevrangebyscore($key, $max, $min, array $options = null)
 * @method int|null    zrevrank($key, $member)
 * @method int         zunionstore($destination, array|string $keys, array $options = null)
 * @method string|null zscore($key, $member)
 * @method array       zscan($key, $cursor, array $options = null)
 * @method array       zrangebylex($key, $start, $stop, array $options = null)
 * @method array       zrevrangebylex($key, $start, $stop, array $options = null)
 * @method int         zremrangebylex($key, $min, $max)
 * @method int         zlexcount($key, $min, $max)
 * @method int         pfadd($key, array $elements)
 * @method mixed       pfmerge($destinationKey, array|string $sourceKeys)
 * @method int         pfcount(array|string $keys)
 * @method mixed       pubsub($subcommand, $argument)
 * @method int         publish($channel, $message)
 * @method mixed       discard()
 * @method array|null  exec()
 * @method mixed       multi()
 * @method mixed       unwatch()
 * @method mixed       watch($key)
 * @method mixed       eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed       evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method mixed       script($subcommand, $argument = null)
 * @method mixed       auth($password)
 * @method string      echo($message)
 * @method mixed       ping($message = null)
 * @method mixed       select($database)
 * @method mixed       bgrewriteaof()
 * @method mixed       bgsave()
 * @method mixed       client($subcommand, $argument = null)
 * @method mixed       config($subcommand, $argument = null)
 * @method int         dbsize()
 * @method mixed       flushall()
 * @method mixed       flushdb()
 * @method array       info($section = null)
 * @method int         lastsave()
 * @method mixed       save()
 * @method mixed       slaveof($host, $port)
 * @method mixed       slowlog($subcommand, $argument = null)
 * @method array       time()
 * @method array       command()
 * @method int         geoadd($key, $longitude, $latitude, $member)
 * @method array       geohash($key, array $members)
 * @method array       geopos($key, array $members)
 * @method string|null geodist($key, $member1, $member2, $unit = null)
 * @method array       georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method array       georadiusbymember($key, $member, $radius, $unit, array $options = null)
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ClientInterface
{
    /**
     * Returns the command factory used by the client.
     *
     * @return FactoryInterface
     */
    public function getCommandFactory();

    /**
     * Returns the client options specified upon initialization.
     *
     * @return OptionsInterface
     */
    public function getOptions();

    /**
     * Opens the underlying connection to the server.
     */
    public function connect();

    /**
     * Closes the underlying connection from the server.
     */
    public function disconnect();

    /**
     * Returns the underlying connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection();

    /**
     * Creates a new instance of the specified Redis command.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return CommandInterface
     */
    public function createCommand($method, $arguments = array());

    /**
     * Executes the specified Redis command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Creates a Redis command with the specified arguments and sends a request
     * to the server.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments);
}
