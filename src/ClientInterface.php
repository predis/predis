<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use Predis\Command\Argument\Geospatial\ByInterface;
use Predis\Command\Argument\Geospatial\FromInterface;
use Predis\Command\Argument\Search\AggregateArguments;
use Predis\Command\Argument\Search\AlterArguments;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\DropArguments;
use Predis\Command\Argument\Search\ExplainArguments;
use Predis\Command\Argument\Search\HybridSearch\HybridSearchQuery;
use Predis\Command\Argument\Search\ProfileArguments;
use Predis\Command\Argument\Search\SchemaFields\FieldInterface;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Command\Argument\Search\SugAddArguments;
use Predis\Command\Argument\Search\SugGetArguments;
use Predis\Command\Argument\Search\SynUpdateArguments;
use Predis\Command\Argument\Server\LimitOffsetCount;
use Predis\Command\Argument\Server\To;
use Predis\Command\Argument\TimeSeries\AddArguments;
use Predis\Command\Argument\TimeSeries\AlterArguments as TSAlterArguments;
use Predis\Command\Argument\TimeSeries\CreateArguments as TSCreateArguments;
use Predis\Command\Argument\TimeSeries\DecrByArguments;
use Predis\Command\Argument\TimeSeries\GetArguments;
use Predis\Command\Argument\TimeSeries\IncrByArguments;
use Predis\Command\Argument\TimeSeries\InfoArguments;
use Predis\Command\Argument\TimeSeries\MGetArguments;
use Predis\Command\Argument\TimeSeries\MRangeArguments;
use Predis\Command\Argument\TimeSeries\RangeArguments;
use Predis\Command\CommandInterface;
use Predis\Command\Container\ACL;
use Predis\Command\Container\CLIENT;
use Predis\Command\Container\FUNCTIONS;
use Predis\Command\Container\HOTKEYS;
use Predis\Command\Container\Json\JSONDEBUG;
use Predis\Command\Container\Search\FTCONFIG;
use Predis\Command\Container\Search\FTCURSOR;
use Predis\Command\Container\XGROUP;
use Predis\Command\Container\XINFO;
use Predis\Command\FactoryInterface;
use Predis\Command\Redis\VADD;
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
 * @method int               copy(string $source, string $destination, int $db = -1, bool $replace = false)
 * @method int               del(string[]|string $keyOrKeys, string ...$keys = null)
 * @method int               delex(string $key, string $flag, $flagValue)
 * @method string            digest(string $key)
 * @method string|null       dump(string $key)
 * @method int               exists(string $key)
 * @method int               expire(string $key, int $seconds, string $expireOption = '')
 * @method int               expireat(string $key, int $timestamp, string $expireOption = '')
 * @method int               expiretime(string $key)
 * @method array             keys(string $pattern)
 * @method int               move(string $key, int $db)
 * @method mixed             object($subcommand, string $key)
 * @method int               persist(string $key)
 * @method int               pexpire(string $key, int $milliseconds, string $option = null)
 * @method int               pexpireat(string $key, int $timestamp, string $option = null)
 * @method int               pttl(string $key)
 * @method string|null       randomkey()
 * @method mixed             rename(string $key, string $target)
 * @method int               renamenx(string $key, string $target)
 * @method array             scan($cursor, ?array $options = null)
 * @method array             sort(string $key, ?array $options = null)
 * @method array             sort_ro(string $key, ?string $byPattern = null, ?LimitOffsetCount $limit = null, array $getPatterns = [], ?string $sorting = null, bool $alpha = false)
 * @method int               ttl(string $key)
 * @method mixed             type(string $key)
 * @method int               append(string $key, $value)
 * @method mixed             bfadd(string $key, $item)
 * @method mixed             bfexists(string $key, $item)
 * @method array             bfinfo(string $key, string $modifier = '')
 * @method array             bfinsert(string $key, int $capacity = -1, float $error = -1, int $expansion = -1, bool $noCreate = false, bool $nonScaling = false, string ...$item)
 * @method Status            bfloadchunk(string $key, int $iterator, $data)
 * @method array             bfmadd(string $key, ...$item)
 * @method array             bfmexists(string $key, ...$item)
 * @method Status            bfreserve(string $key, float $errorRate, int $capacity, int $expansion = -1, bool $nonScaling = false)
 * @method array             bfscandump(string $key, int $iterator)
 * @method int               bitcount(string $key, $start = null, $end = null, string $index = 'byte')
 * @method int               bitop($operation, $destkey, $key)
 * @method array|null        bitfield(string $key, $subcommand, ...$subcommandArg)
 * @method array|null        bitfield_ro(string $key, ?array $encodingOffsetMap = null)
 * @method int               bitpos(string $key, $bit, $start = null, $end = null, string $index = 'byte')
 * @method array             blmpop(int $timeout, array $keys, string $modifier = 'left', int $count = 1)
 * @method array             bzpopmax(array $keys, int $timeout)
 * @method array             bzpopmin(array $keys, int $timeout)
 * @method array             bzmpop(int $timeout, array $keys, string $modifier = 'min', int $count = 1)
 * @method mixed             cfadd(string $key, $item)
 * @method mixed             cfaddnx(string $key, $item)
 * @method int               cfcount(string $key, $item)
 * @method mixed             cfdel(string $key, $item)
 * @method mixed             cfexists(string $key, $item)
 * @method Status            cfloadchunk(string $key, int $iterator, $data)
 * @method int               cfmexists(string $key, ...$item)
 * @method array             cfinfo(string $key)
 * @method array             cfinsert(string $key, int $capacity = -1, bool $noCreate = false, string ...$item)
 * @method array             cfinsertnx(string $key, int $capacity = -1, bool $noCreate = false, string ...$item)
 * @method Status            cfreserve(string $key, int $capacity, int $bucketSize = -1, int $maxIterations = -1, int $expansion = -1)
 * @method array             cfscandump(string $key, int $iterator)
 * @method array             cmsincrby(string $key, string|int ...$itemIncrementDictionary)
 * @method array             cmsinfo(string $key)
 * @method Status            cmsinitbydim(string $key, int $width, int $depth)
 * @method Status            cmsinitbyprob(string $key, float $errorRate, float $probability)
 * @method Status            cmsmerge(string $destination, array $sources, array $weights = [])
 * @method array             cmsquery(string $key, string ...$item)
 * @method int               decr(string $key)
 * @method int               decrby(string $key, int $decrement)
 * @method Status            failover(?To $to = null, bool $abort = false, int $timeout = -1)
 * @method mixed             fcall(string $function, array $keys, ...$args)
 * @method mixed             fcall_ro(string $function, array $keys, ...$args)
 * @method array             ft_list()
 * @method array             ftaggregate(string $index, string $query, ?AggregateArguments $arguments = null)
 * @method Status            ftaliasadd(string $alias, string $index)
 * @method Status            ftaliasdel(string $alias)
 * @method Status            ftaliasupdate(string $alias, string $index)
 * @method Status            ftalter(string $index, FieldInterface[] $schema, ?AlterArguments $arguments = null)
 * @method Status            ftcreate(string $index, FieldInterface[] $schema, ?CreateArguments $arguments = null)
 * @method int               ftdictadd(string $dict, ...$term)
 * @method int               ftdictdel(string $dict, ...$term)
 * @method array             ftdictdump(string $dict)
 * @method Status            ftdropindex(string $index, ?DropArguments $arguments = null)
 * @method string            ftexplain(string $index, string $query, ?ExplainArguments $arguments = null)
 * @method array             fthybrid(string $index, HybridSearchQuery $query)
 * @method array             ftinfo(string $index)
 * @method array             ftprofile(string $index, ProfileArguments $arguments)
 * @method array             ftsearch(string $index, string $query, ?SearchArguments $arguments = null)
 * @method array             ftspellcheck(string $index, string $query, ?SearchArguments $arguments = null)
 * @method int               ftsugadd(string $key, string $string, float $score, ?SugAddArguments $arguments = null)
 * @method int               ftsugdel(string $key, string $string)
 * @method array             ftsugget(string $key, string $prefix, ?SugGetArguments $arguments = null)
 * @method int               ftsuglen(string $key)
 * @method array             ftsyndump(string $index)
 * @method Status            ftsynupdate(string $index, string $synonymGroupId, ?SynUpdateArguments $arguments = null, string ...$terms)
 * @method array             fttagvals(string $index, string $fieldName)
 * @method string|null       get(string $key)
 * @method int               getbit(string $key, $offset)
 * @method int|null          getex(string $key, $modifier = '', $value = false)
 * @method string            getrange(string $key, $start, $end)
 * @method string            getdel(string $key)
 * @method string|null       getset(string $key, $value)
 * @method int               incr(string $key)
 * @method int               incrby(string $key, int $increment)
 * @method string            incrbyfloat(string $key, int|float $increment)
 * @method array             mget(string[]|string $keyOrKeys, string ...$keys = null)
 * @method mixed             mset(array $dictionary)
 * @method array             msetex(array $dictionary, ?string $existModifier = null, ?string $expireResolution = null, ?int $expireTTL = null)
 * @method int               msetnx(array $dictionary)
 * @method Status            psetex(string $key, $milliseconds, $value)
 * @method Status|null       set(string $key, $value, $expireResolution = null, $expireTTL = null, $flag = null, $flagValue = null)
 * @method int               setbit(string $key, $offset, $value)
 * @method Status            setex(string $key, $seconds, $value)
 * @method int               setnx(string $key, $value)
 * @method int               setrange(string $key, $offset, $value)
 * @method int               strlen(string $key)
 * @method int               hdel(string $key, array $fields)
 * @method int               hexists(string $key, string $field)
 * @method array|null        hexpire(string $key, int $seconds, array $fields, string $flag = null)
 * @method array|null        hexpireat(string $key, int $unixTimeSeconds, array $fields, string $flag = null)
 * @method array|null        hexpiretime(string $key, array $fields)
 * @method array|null        hpersist(string $key, array $fields)
 * @method array|null        hpexpire(string $key, int $milliseconds, array $fields, string $flag = null)
 * @method array|null        hpexpireat(string $key, int $unixTimeMilliseconds, array $fields, string $flag = null)
 * @method array|null        hpexpiretime(string $key, array $fields)
 * @method string|null       hget(string $key, string $field)
 * @method array|null        hgetex(string $key, array $fields, string $modifier = HGETEX::NULL, int|bool $modifierValue = false)
 * @method array             hgetall(string $key)
 * @method array             hgetdel(string $key, array $fields)
 * @method int               hincrby(string $key, string $field, int $increment)
 * @method string            hincrbyfloat(string $key, string $field, int|float $increment)
 * @method array             hkeys(string $key)
 * @method int               hlen(string $key)
 * @method array             hmget(string $key, array $fields)
 * @method mixed             hmset(string $key, array $dictionary)
 * @method array             hrandfield(string $key, int $count = 1, bool $withValues = false)
 * @method array             hscan(string $key, $cursor, ?array $options = null)
 * @method int               hset(string $key, string $field, string $value)
 * @method int               hsetex(string $key, array $fieldValueMap, string $setModifier = HSETEX::SET_NULL, string $ttlModifier = HSETEX::TTL_NULL, int|bool $ttlModifierValue = false)
 * @method int               hsetnx(string $key, string $field, string $value)
 * @method array|null        httl(string $key, array $fields)
 * @method array|null        hpttl(string $key, array $fields)
 * @method array             hvals(string $key)
 * @method int               hstrlen(string $key, string $field)
 * @method array             jsonarrappend(string $key, string $path = '$', ...$value)
 * @method array             jsonarrindex(string $key, string $path, string $value, int $start = 0, int $stop = 0)
 * @method array             jsonarrinsert(string $key, string $path, int $index, string ...$value)
 * @method array             jsonarrlen(string $key, string $path = '$')
 * @method array             jsonarrpop(string $key, string $path = '$', int $index = -1)
 * @method int               jsonclear(string $key, string $path = '$')
 * @method array             jsonarrtrim(string $key, string $path, int $start, int $stop)
 * @method int               jsondel(string $key, string $path = '$')
 * @method int               jsonforget(string $key, string $path = '$')
 * @method mixed             jsonget(string $key, string $indent = '', string $newline = '', string $space = '', string ...$paths)
 * @method mixed             jsonnumincrby(string $key, string $path, int $value)
 * @method Status            jsonmerge(string $key, string $path, string $value)
 * @method array             jsonmget(array $keys, string $path)
 * @method Status            jsonmset(string ...$keyPathValue)
 * @method array             jsonobjkeys(string $key, string $path = '$')
 * @method array             jsonobjlen(string $key, string $path = '$')
 * @method array             jsonresp(string $key, string $path = '$')
 * @method string            jsonset(string $key, string $path, string $value, ?string $subcommand = null)
 * @method array             jsonstrappend(string $key, string $path, string $value)
 * @method array             jsonstrlen(string $key, string $path = '$')
 * @method array             jsontoggle(string $key, string $path)
 * @method array             jsontype(string $key, string $path = '$')
 * @method string            blmove(string $source, string $destination, string $where, string $to, int $timeout)
 * @method array|null        blpop(array|string $keys, int|float $timeout)
 * @method array|null        brpop(array|string $keys, int|float $timeout)
 * @method string|null       brpoplpush(string $source, string $destination, int|float $timeout)
 * @method mixed             lcs(string $key1, string $key2, bool $len = false, bool $idx = false, int $minMatchLen = 0, bool $withMatchLen = false)
 * @method string|null       lindex(string $key, int $index)
 * @method int               linsert(string $key, $whence, $pivot, $value)
 * @method int               llen(string $key)
 * @method string            lmove(string $source, string $destination, string $where, string $to)
 * @method array|null        lmpop(array $keys, string $modifier = 'left', int $count = 1)
 * @method string|null       lpop(string $key)
 * @method int               lpush(string $key, array $values)
 * @method int               lpushx(string $key, array $values)
 * @method string[]          lrange(string $key, int $start, int $stop)
 * @method int               lrem(string $key, int $count, string $value)
 * @method mixed             lset(string $key, int $index, string $value)
 * @method mixed             ltrim(string $key, int $start, int $stop)
 * @method string|null       rpop(string $key)
 * @method string|null       rpoplpush(string $source, string $destination)
 * @method int               rpush(string $key, array $values)
 * @method int               rpushx(string $key, array $values)
 * @method int               sadd(string $key, array $members)
 * @method int               scard(string $key)
 * @method string[]          sdiff(array|string $keys)
 * @method int               sdiffstore(string $destination, array|string $keys)
 * @method string[]          sinter(array|string $keys)
 * @method int               sintercard(array $keys, int $limit = 0)
 * @method int               sinterstore(string $destination, array|string $keys)
 * @method int               sismember(string $key, string $member)
 * @method string[]          smembers(string $key)
 * @method array             smismember(string $key, string ...$members)
 * @method int               smove(string $source, string $destination, string $member)
 * @method string|array|null spop(string $key, ?int $count = null)
 * @method string|null       srandmember(string $key, ?int $count = null)
 * @method int               srem(string $key, array|string $member)
 * @method array             sscan(string $key, int $cursor, array $options = null)
 * @method array             ssubscribe(string ...$shardChannels)
 * @method array             subscribe(string ...$channels)
 * @method string[]          sunion(array|string $keys)
 * @method int               sunionstore(string $destination, array|string $keys)
 * @method array             sunsubscribe(?string ...$shardChannels = null)
 * @method int               touch(string[]|string $keyOrKeys, string ...$keys = null)
 * @method Status            tdigestadd(string $key, float ...$value)
 * @method array             tdigestbyrank(string $key, int ...$rank)
 * @method array             tdigestbyrevrank(string $key, int ...$reverseRank)
 * @method array             tdigestcdf(string $key, int ...$value)
 * @method Status            tdigestcreate(string $key, int $compression = 0)
 * @method array             tdigestinfo(string $key)
 * @method mixed             tdigestmax(string $key)
 * @method Status            tdigestmerge(string $destinationKey, array $sourceKeys, int $compression = 0, bool $override = false)
 * @method string[]          tdigestquantile(string $key, float ...$quantile)
 * @method mixed             tdigestmin(string $key)
 * @method array             tdigestrank(string $key, float ...$value)
 * @method Status            tdigestreset(string $key)
 * @method array             tdigestrevrank(string $key, float ...$value)
 * @method string            tdigesttrimmed_mean(string $key, float $lowCutQuantile, float $highCutQuantile)
 * @method array             topkadd(string $key, ...$items)
 * @method array             topkincrby(string $key, ...$itemIncrement)
 * @method array             topkinfo(string $key)
 * @method array             topklist(string $key, bool $withCount = false)
 * @method array             topkquery(string $key, ...$items)
 * @method Status            topkreserve(string $key, int $topK, int $width = 8, int $depth = 7, float $decay = 0.9)
 * @method int               tsadd(string $key, int $timestamp, string|float $value, ?AddArguments $arguments = null)
 * @method Status            tsalter(string $key, ?TSAlterArguments $arguments = null)
 * @method Status            tscreate(string $key, ?TSCreateArguments $arguments = null)
 * @method Status            tscreaterule(string $sourceKey, string $destKey, string $aggregator, int $bucketDuration, int $alignTimestamp = 0)
 * @method int               tsdecrby(string $key, float $value, ?DecrByArguments $arguments = null)
 * @method int               tsdel(string $key, int $fromTimestamp, int $toTimestamp)
 * @method Status            tsdeleterule(string $sourceKey, string $destKey)
 * @method array             tsget(string $key, ?GetArguments $arguments = null)
 * @method int               tsincrby(string $key, float $value, ?IncrByArguments $arguments = null)
 * @method array             tsinfo(string $key, ?InfoArguments $arguments = null)
 * @method array             tsmadd(mixed ...$keyTimestampValue)
 * @method array             tsmget(MGetArguments $arguments, string ...$filterExpression)
 * @method array             tsmrange($fromTimestamp, $toTimestamp, MRangeArguments $arguments)
 * @method array             tsmrevrange($fromTimestamp, $toTimestamp, MRangeArguments $arguments)
 * @method array             tsqueryindex(string ...$filterExpression)
 * @method array             tsrange(string $key, $fromTimestamp, $toTimestamp, ?RangeArguments $arguments = null)
 * @method array             tsrevrange(string $key, $fromTimestamp, $toTimestamp, ?RangeArguments $arguments = null)
 * @method int               xack(string $key, string $group, string ...$id)
 * @method array             xackdel(string $key, string $group, string $mode, array $ids)
 * @method string            xadd(string $key, array $dictionary, string $id = '*', array $options = null)
 * @method array             xautoclaim(string $key, string $group, string $consumer, int $minIdleTime, string $start, ?int $count = null, bool $justId = false)
 * @method array             xclaim(string $key, string $group, string $consumer, int $minIdleTime, string|array $ids, ?int $idle = null, ?int $time = null, ?int $retryCount = null, bool $force = false, bool $justId = false, ?string $lastId = null)
 * @method Status            xcfgset(string $key, ?int $duration = null, ?int $maxsize = null)
 * @method int               xdel(string $key, string ...$id)
 * @method array             xdelex(string $key, string $mode, array $ids)
 * @method int               xlen(string $key)
 * @method array             xpending(string $key, string $group, ?int $minIdleTime = null, ?string $start = null, ?string $end = null, ?int $count = null, ?string $consumer = null)
 * @method array             xrevrange(string $key, string $end, string $start, ?int $count = null)
 * @method array             xrange(string $key, string $start, string $end, ?int $count = null)
 * @method array|null        xread(int $count = null, int $block = null, array $streams = null, string ...$id)
 * @method array             xreadgroup(string $group, string $consumer, ?int $count = null, ?int $blockMs = null, bool $noAck = false, string ...$keyOrId)
 * @method array             xreadgroup_claim(string $group, string $consumer, array $keyIdDict, ?int $count = null, ?int $blockMs = null, bool $noAck = false, ?int $claim = null)
 * @method Status            xsetid(string $key, string $lastId, ?int $entriesAdded = null, ?string $maxDeleteId = null)
 * @method string            xtrim(string $key, array|string $strategy, string $threshold, array $options = null)
 * @method int               zadd(string $key, array $membersAndScoresDictionary)
 * @method int               zcard(string $key)
 * @method int               zcount(string $key, int|string $min, int|string $max)
 * @method array             zdiff(array $keys, bool $withScores = false)
 * @method int               zdiffstore(string $destination, array $keys)
 * @method string            zincrby(string $key, int $increment, string $member)
 * @method int               zintercard(array $keys, int $limit = 0)
 * @method int               zinterstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method array             zinter(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method array             zmpop(array $keys, string $modifier = 'min', int $count = 1)
 * @method array             zmscore(string $key, string ...$member)
 * @method array             zpopmin(string $key, int $count = 1)
 * @method array             zpopmax(string $key, int $count = 1)
 * @method mixed             zrandmember(string $key, int $count = 1, bool $withScores = false)
 * @method array             zrange(string $key, int|string $start, int|string $stop, ?array $options = null)
 * @method array             zrangebyscore(string $key, int|string $min, int|string $max, ?array $options = null)
 * @method int               zrangestore(string $destination, string $source, int|string $min, int|string $max, string|bool $by = false, bool $reversed = false, bool $limit = false, int $offset = 0, int $count = 0)
 * @method int|null          zrank(string $key, string $member)
 * @method int               zrem(string $key, string ...$member)
 * @method int               zremrangebyrank(string $key, int|string $start, int|string $stop)
 * @method int               zremrangebyscore(string $key, int|string $min, int|string $max)
 * @method array             zrevrange(string $key, int|string $start, int|string $stop, ?array $options = null)
 * @method array             zrevrangebyscore(string $key, int|string $max, int|string $min, ?array $options = null)
 * @method int|null          zrevrank(string $key, string $member)
 * @method array             zunion(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method int               zunionstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method string|null       zscore(string $key, string $member)
 * @method array             zscan(string $key, int $cursor, ?array $options = null)
 * @method array             zrangebylex(string $key, string $start, string $stop, ?array $options = null)
 * @method array             zrevrangebylex(string $key, string $start, string $stop, ?array $options = null)
 * @method int               zremrangebylex(string $key, string $min, string $max)
 * @method int               zlexcount(string $key, string $min, string $max)
 * @method int               pexpiretime(string $key)
 * @method int               pfadd(string $key, array $elements)
 * @method mixed             pfmerge(string $destinationKey, array|string $sourceKeys)
 * @method int               pfcount(string[]|string $keyOrKeys, string ...$keys = null)
 * @method mixed             pubsub($subcommand, $argument)
 * @method int               publish($channel, $message)
 * @method mixed             discard()
 * @method array|null        exec()
 * @method mixed             multi()
 * @method mixed             unwatch()
 * @method array             unsubscribe(string ...$channels)
 * @method bool              vadd(string $key, string|array $vector, string $elem, int $dim = null, bool $cas = false, string $quant = VADD::QUANT_DEFAULT, int $bef = null, string|array $attributes = null, int $numlinks = null)
 * @method int               vcard(string $key)
 * @method int               vdim(string $key)
 * @method array             vemb(string $key, string $elem, bool $raw = false)
 * @method string|array|null vgetattr(string $key, string $elem, bool $asJson = false)
 * @method array|null        vinfo(string $key)
 * @method array|null        vlinks(string $key, string $elem, bool $withScores = false)
 * @method string|array|null vrandmember(string $key, int $count = null)
 * @method array             vrange(string $key, string $start, string $end, int $count = null)
 * @method bool              vrem(string $key, string $elem)
 * @method array             vsim(string $key, string|array $vectorOrElem, bool $isElem = false, bool $withScores = false, int $count = null, float $epsilon = null, int $ef = null, string $filter = null, int $filterEf = null, bool $truth = false, bool $noThread = false)
 * @method bool              vsetattr(string $key, string $elem, string|array $attributes)
 * @method array             waitaof(int $numLocal, int $numReplicas, int $timeout)
 * @method mixed             watch(string[]|string $keyOrKeys)
 * @method mixed             eval(string $script, int $numkeys, string ...$keyOrArg = null)
 * @method mixed             eval_ro(string $script, array $keys, ...$argument)
 * @method mixed             evalsha(string $script, int $numkeys, string ...$keyOrArg = null)
 * @method mixed             evalsha_ro(string $sha1, array $keys, ...$argument)
 * @method mixed             script($subcommand, $argument = null)
 * @method Status            shutdown(?bool $noSave = null, bool $now = false, bool $force = false, bool $abort = false)
 * @method mixed             auth(string $password)
 * @method string            echo(string $message)
 * @method mixed             ping(?string $message = null)
 * @method mixed             select(int $database)
 * @method mixed             bgrewriteaof()
 * @method mixed             bgsave()
 * @method mixed             config($subcommand, $argument = null)
 * @method int               dbsize()
 * @method mixed             flushall()
 * @method mixed             flushdb()
 * @method array             info(string ...$section = null)
 * @method int               lastsave()
 * @method mixed             save()
 * @method mixed             slaveof(string $host, int $port)
 * @method mixed             slowlog($subcommand, $argument = null)
 * @method int               spublish(string $shardChannel, string $message)
 * @method array             time()
 * @method array             command($subcommand, $argument = null)
 * @method int               geoadd(string $key, $longitude, $latitude, $member)
 * @method array             geohash(string $key, array $members)
 * @method array             geopos(string $key, array $members)
 * @method string|null       geodist(string $key, $member1, $member2, $unit = null)
 * @method array             georadius(string $key, $longitude, $latitude, $radius, $unit, ?array $options = null)
 * @method array             georadiusbymember(string $key, $member, $radius, $unit, ?array $options = null)
 * @method array             geosearch(string $key, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $withCoord = false, bool $withDist = false, bool $withHash = false)
 * @method int               geosearchstore(string $destination, string $source, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $storeDist = false)
 *
 * Container commands
 * @property CLIENT    $client
 * @property HOTKEYS   $hotkeys
 * @property FUNCTIONS $function
 * @property FTCONFIG  $ftconfig
 * @property FTCURSOR  $ftcursor
 * @property JSONDEBUG $jsondebug
 * @property ACL       $acl
 * @property XGROUP    $xgroup
 * @property XINFO     $xinfo
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
    public function createCommand($method, $arguments = []);

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
