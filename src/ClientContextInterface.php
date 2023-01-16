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

use Predis\Command\Argument\Geospatial\ByInterface;
use Predis\Command\Argument\Geospatial\FromInterface;
use Predis\Command\Argument\Server\To;
use Predis\Command\CommandInterface;

/**
 * Interface defining a client-side context such as a pipeline or transaction.
 *
 * @method $this copy(string $source, string $destination, int $db = -1, bool $replace = false)
 * @method $this del(array|string $keys)
 * @method $this dump($key)
 * @method $this exists($key)
 * @method $this expire($key, $seconds)
 * @method $this expireat($key, $timestamp)
 * @method $this keys($pattern)
 * @method $this move($key, $db)
 * @method $this object($subcommand, $key)
 * @method $this persist($key)
 * @method $this pexpire($key, $milliseconds)
 * @method $this pexpireat($key, $timestamp)
 * @method $this pttl($key)
 * @method $this randomkey()
 * @method $this rename($key, $target)
 * @method $this renamenx($key, $target)
 * @method $this scan($cursor, array $options = null)
 * @method $this sort($key, array $options = null)
 * @method $this ttl($key)
 * @method $this type($key)
 * @method $this append($key, $value)
 * @method $this bitcount($key, $start = null, $end = null)
 * @method $this bitop($operation, $destkey, $key)
 * @method $this bitfield($key, $subcommand, ...$subcommandArg)
 * @method $this bitpos($key, $bit, $start = null, $end = null)
 * @method $this blmpop(int $timeout, array $keys, string $modifier = 'left', int $count = 1)
 * @method $this bzpopmax(array $keys, int $timeout)
 * @method $this bzpopmin(array $keys, int $timeout)
 * @method $this bzmpop(int $timeout, array $keys, string $modifier = 'min', int $count = 1)
 * @method $this decr($key)
 * @method $this decrby($key, $decrement)
 * @method $this failover(?To $to = null, bool $abort = false, int $timeout = -1)
 * @method $this get($key)
 * @method $this getbit($key, $offset)
 * @method $this getex(string $key, $modifier = '', $value = false)
 * @method $this getrange($key, $start, $end)
 * @method $this getdel(string $key)
 * @method $this getset($key, $value)
 * @method $this incr($key)
 * @method $this incrby($key, $increment)
 * @method $this incrbyfloat($key, $increment)
 * @method $this mget(array $keys)
 * @method $this mset(array $dictionary)
 * @method $this msetnx(array $dictionary)
 * @method $this psetex($key, $milliseconds, $value)
 * @method $this set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null)
 * @method $this setbit($key, $offset, $value)
 * @method $this setex($key, $seconds, $value)
 * @method $this setnx($key, $value)
 * @method $this setrange($key, $offset, $value)
 * @method $this strlen($key)
 * @method $this hdel($key, array $fields)
 * @method $this hexists($key, $field)
 * @method $this hget($key, $field)
 * @method $this hgetall($key)
 * @method $this hincrby($key, $field, $increment)
 * @method $this hincrbyfloat($key, $field, $increment)
 * @method $this hkeys($key)
 * @method $this hlen($key)
 * @method $this hmget($key, array $fields)
 * @method $this hmset($key, array $dictionary)
 * @method $this hrandfield(string $key, int $count = 1, bool $withValues = false)
 * @method $this hscan($key, $cursor, array $options = null)
 * @method $this hset($key, $field, $value)
 * @method $this hsetnx($key, $field, $value)
 * @method $this hvals($key)
 * @method $this hstrlen($key, $field)
 * @method $this blmove(string $source, string $destination, string $where, string $to, int $timeout)
 * @method $this blpop(array|string $keys, $timeout)
 * @method $this brpop(array|string $keys, $timeout)
 * @method $this brpoplpush($source, $destination, $timeout)
 * @method $this lindex($key, $index)
 * @method $this linsert($key, $whence, $pivot, $value)
 * @method $this llen($key)
 * @method $this lmove(string $source, string $destination, string $where, string $to)
 * @method $this lmpop(array $keys, string $modifier = 'left', int $count = 1)
 * @method $this lpop($key)
 * @method $this lpush($key, array $values)
 * @method $this lpushx($key, array $values)
 * @method $this lrange($key, $start, $stop)
 * @method $this lrem($key, $count, $value)
 * @method $this lset($key, $index, $value)
 * @method $this ltrim($key, $start, $stop)
 * @method $this rpop($key)
 * @method $this rpoplpush($source, $destination)
 * @method $this rpush($key, array $values)
 * @method $this rpushx($key, array $values)
 * @method $this sadd($key, array $members)
 * @method $this scard($key)
 * @method $this sdiff(array|string $keys)
 * @method $this sdiffstore($destination, array|string $keys)
 * @method $this sinter(array|string $keys)
 * @method $this sinterstore($destination, array|string $keys)
 * @method $this sismember($key, $member)
 * @method $this smembers($key)
 * @method $this smismember(string $key, string ...$members)
 * @method $this smove($source, $destination, $member)
 * @method $this spop($key, $count = null)
 * @method $this srandmember($key, $count = null)
 * @method $this srem($key, $member)
 * @method $this sscan($key, $cursor, array $options = null)
 * @method $this sunion(array|string $keys)
 * @method $this sunionstore($destination, array|string $keys)
 * @method $this zadd($key, array $membersAndScoresDictionary)
 * @method $this zcard($key)
 * @method $this zcount($key, $min, $max)
 * @method $this zdiff(array $keys, bool $withScores = false)
 * @method $this zdiffstore(string $destination, array $keys)
 * @method $this zincrby($key, $increment, $member)
 * @method $this zintercard(array $keys, int $limit = 0)
 * @method $this zinterstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method $this zinter(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method $this zmpop(array $keys, string $modifier = 'min', int $count = 1)
 * @method $this zmscore(string $key, string ...$member)
 * @method $this zrandmember(string $key, int $count = 1, bool $withScores = false)
 * @method $this zrange($key, $start, $stop, array $options = null)
 * @method $this zrangebyscore($key, $min, $max, array $options = null)
 * @method $this zrangestore(string $destination, string $source, int|string $min, string|int $max, string|bool $by = false, bool $reversed = false, bool $limit = false, int $offset = 0, int $count = 0)
 * @method $this zrank($key, $member)
 * @method $this zrem($key, $member)
 * @method $this zremrangebyrank($key, $start, $stop)
 * @method $this zremrangebyscore($key, $min, $max)
 * @method $this zrevrange($key, $start, $stop, array $options = null)
 * @method $this zrevrangebyscore($key, $max, $min, array $options = null)
 * @method $this zrevrank($key, $member)
 * @method $this zunion(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method $this zunionstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method $this zscore($key, $member)
 * @method $this zscan($key, $cursor, array $options = null)
 * @method $this zrangebylex($key, $start, $stop, array $options = null)
 * @method $this zrevrangebylex($key, $start, $stop, array $options = null)
 * @method $this zremrangebylex($key, $min, $max)
 * @method $this zlexcount($key, $min, $max)
 * @method $this pfadd($key, array $elements)
 * @method $this pfmerge($destinationKey, array|string $sourceKeys)
 * @method $this pfcount(array|string $keys)
 * @method $this pubsub($subcommand, $argument)
 * @method $this publish($channel, $message)
 * @method $this discard()
 * @method $this exec()
 * @method $this multi()
 * @method $this unwatch()
 * @method $this watch($key)
 * @method $this eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method $this evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method $this script($subcommand, $argument = null)
 * @method $this auth($password)
 * @method $this echo($message)
 * @method $this ping($message = null)
 * @method $this select($database)
 * @method $this bgrewriteaof()
 * @method $this bgsave()
 * @method $this client($subcommand, $argument = null)
 * @method $this config($subcommand, $argument = null)
 * @method $this dbsize()
 * @method $this flushall()
 * @method $this flushdb()
 * @method $this info($section = null)
 * @method $this lastsave()
 * @method $this save()
 * @method $this slaveof($host, $port)
 * @method $this slowlog($subcommand, $argument = null)
 * @method $this time()
 * @method $this command()
 * @method $this geoadd($key, $longitude, $latitude, $member)
 * @method $this geohash($key, array $members)
 * @method $this geopos($key, array $members)
 * @method $this geodist($key, $member1, $member2, $unit = null)
 * @method $this georadius($key, $longitude, $latitude, $radius, $unit, array $options = null)
 * @method $this georadiusbymember($key, $member, $radius, $unit, array $options = null)
 * @method $this geosearch(string $key, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $withCoord = false, bool $withDist = false, bool $withHash = false)
 * @method $this geosearchstore(string $destination, string $source, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $storeDist = false)
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
interface ClientContextInterface
{
    /**
     * Sends the specified command instance to Redis.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function executeCommand(CommandInterface $command);

    /**
     * Sends the specified command with its arguments to Redis.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return mixed
     */
    public function __call($method, $arguments);

    /**
     * Starts the execution of the context.
     *
     * @param mixed $callable Optional callback for execution.
     *
     * @return array
     */
    public function execute($callable = null);
}
