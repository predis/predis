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
 * @method int         del(string[]|string $keyOrKeys, string ...$keys = null)
 * @method string|null dump(string $key)
 * @method int         exists(string $key)
 * @method int         expire(string $key, int $seconds)
 * @method int         expireat(string $key, int $timestamp)
 * @method array       keys(string $pattern)
 * @method int         move(string $key, int $db)
 * @method mixed       object($subcommand, string $key)
 * @method int         persist(string $key)
 * @method int         pexpire(string $key, int $milliseconds)
 * @method int         pexpireat(string $key, int $timestamp)
 * @method int         pttl(string $key)
 * @method string|null randomkey()
 * @method mixed       rename(string $key, string $target)
 * @method int         renamenx(string $key, string $target)
 * @method array       scan($cursor, array $options = null)
 * @method array       sort(string $key, array $options = null)
 * @method int         ttl(string $key)
 * @method mixed       type(string $key)
 * @method int         append(string $key, $value)
 * @method int         bitcount(string $key, $start = null, $end = null)
 * @method int         bitop($operation, $destkey, $key)
 * @method array|null  bitfield(string $key, $subcommand, ...$subcommandArg)
 * @method int         bitpos(string $key, $bit, $start = null, $end = null)
 * @method int         decr(string $key)
 * @method int         decrby(string $key, int $decrement)
 * @method string|null get(string $key)
 * @method int         getbit(string $key, $offset)
 * @method string      getrange(string $key, $start, $end)
 * @method string|null getset(string $key, $value)
 * @method int         incr(string $key)
 * @method int         incrby(string $key, int $increment)
 * @method string      incrbyfloat(string $key, int|float $increment)
 * @method array       mget(string[]|string $keyOrKeys, string ...$keys = null)
 * @method mixed       mset(array $dictionary)
 * @method int         msetnx(array $dictionary)
 * @method Status      psetex(string $key, $milliseconds, $value)
 * @method Status      set(string $key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method int         setbit(string $key, $offset, $value)
 * @method Status      setex(string $key, $seconds, $value)
 * @method int         setnx(string $key, $value)
 * @method int         setrange(string $key, $offset, $value)
 * @method int         strlen(string $key)
 * @method int         hdel(string $key, array $fields)
 * @method int         hexists(string $key, string $field)
 * @method string|null hget(string $key, string $field)
 * @method array       hgetall(string $key)
 * @method int         hincrby(string $key, string $field, int $increment)
 * @method string      hincrbyfloat(string $key, string $field, int|float $increment)
 * @method array       hkeys(string $key)
 * @method int         hlen(string $key)
 * @method array       hmget(string $key, array $fields)
 * @method mixed       hmset(string $key, array $dictionary)
 * @method array       hscan(string $key, $cursor, array $options = null)
 * @method int         hset(string $key, string $field, string $value)
 * @method int         hsetnx(string $key, string $field, string $value)
 * @method array       hvals(string $key)
 * @method int         hstrlen(string $key, string $field)
 * @method array|null  blpop(array|string $keys, int|float $timeout)
 * @method array|null  brpop(array|string $keys, int|float $timeout)
 * @method string|null brpoplpush(string $source, string $destination, int|float $timeout)
 * @method string|null lindex(string $key, int $index)
 * @method int         linsert(string $key, $whence, $pivot, $value)
 * @method int         llen(string $key)
 * @method string|null lpop(string $key)
 * @method int         lpush(string $key, array $values)
 * @method int         lpushx(string $key, array $values)
 * @method string[]    lrange(string $key, int $start, int $stop)
 * @method int         lrem(string $key, int $count, string $value)
 * @method mixed       lset(string $key, int $index, string $value)
 * @method mixed       ltrim(string $key, int $start, int $stop)
 * @method string|null rpop(string $key)
 * @method string|null rpoplpush(string $source, string $destination)
 * @method int         rpush(string $key, array $values)
 * @method int         rpushx(string $key, array $values)
 * @method int         sadd(string $key, array $members)
 * @method int         scard(string $key)
 * @method string[]    sdiff(array|string $keys)
 * @method int         sdiffstore(string $destination, array|string $keys)
 * @method string[]    sinter(array|string $keys)
 * @method int         sinterstore(string $destination, array|string $keys)
 * @method int         sismember(string $key, string $member)
 * @method string[]    smembers(string $key)
 * @method int         smove(string $source, string $destination, string $member)
 * @method string|null spop(string $key, int $count = null)
 * @method string|null srandmember(string $key, int $count = null)
 * @method int         srem(string $key, string $member)
 * @method array       sscan(string $key, int $cursor, array $options = null)
 * @method string[]    sunion(array|string $keys)
 * @method int         sunionstore(string $destination, array|string $keys)
 * @method int         touch(string[]|string $keyOrKeys, string ...$keys = null)
 * @method int         zadd(string $key, array $membersAndScoresDictionary)
 * @method int         zcard(string $key)
 * @method string      zcount(string $key, int|string $min, int|string $max)
 * @method string      zincrby(string $key, int $increment, string $member)
 * @method int         zinterstore(string $destination, array|string $keys, array $options = null)
 * @method array       zpopmin(string $key, int $count = 1)
 * @method array       zpopmax(string $key, int $count = 1)
 * @method array       zrange(string $key, int|string $start, int|string $stop, array $options = null)
 * @method array       zrangebyscore(string $key, int|string $min, int|string $max, array $options = null)
 * @method int|null    zrank(string $key, string $member)
 * @method int         zrem(string $key, string ...$member)
 * @method int         zremrangebyrank(string $key, int|string $start, int|string $stop)
 * @method int         zremrangebyscore(string $key, int|string $min, int|string $max)
 * @method array       zrevrange(string $key, int|string $start, int|string $stop, array $options = null)
 * @method array       zrevrangebyscore(string $key, int|string $max, int|string $min, array $options = null)
 * @method int|null    zrevrank(string $key, string $member)
 * @method int         zunionstore(string $destination, array|string $keys, array $options = null)
 * @method string|null zscore(string $key, string $member)
 * @method array       zscan(string $key, int $cursor, array $options = null)
 * @method array       zrangebylex(string $key, string $start, string $stop, array $options = null)
 * @method array       zrevrangebylex(string $key, string $start, string $stop, array $options = null)
 * @method int         zremrangebylex(string $key, string $min, string $max)
 * @method int         zlexcount(string $key, string $min, string $max)
 * @method int         pfadd(string $key, array $elements)
 * @method mixed       pfmerge(string $destinationKey, array|string $sourceKeys)
 * @method int         pfcount(string[]|string $keyOrKeys, string ...$keys = null)
 * @method mixed       pubsub($subcommand, $argument)
 * @method int         publish($channel, $message)
 * @method mixed       discard()
 * @method array|null  exec()
 * @method mixed       multi()
 * @method mixed       unwatch()
 * @method mixed       watch(string $key)
 * @method mixed       eval(string $script, int $numkeys, string ...$keyOrArg = null)
 * @method mixed       evalsha(string $script, int $numkeys, string ...$keyOrArg = null)
 * @method mixed       script($subcommand, $argument = null)
 * @method mixed       auth(string $password)
 * @method string      echo(string $message)
 * @method mixed       ping(string $message = null)
 * @method mixed       select(int $database)
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
 * @method mixed       slaveof(string $host, int $port)
 * @method mixed       slowlog($subcommand, $argument = null)
 * @method array       time()
 * @method array       command()
 * @method int         geoadd(string $key, $longitude, $latitude, $member)
 * @method array       geohash(string $key, array $members)
 * @method array       geopos(string $key, array $members)
 * @method string|null geodist(string $key, $member1, $member2, $unit = null)
 * @method array       georadius(string $key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method array       georadiusbymember(string $key, $member, $radius, $unit, array $options = null)
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
