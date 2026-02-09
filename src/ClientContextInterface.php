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
use Predis\Command\Redis\VADD;

/**
 * Interface defining a client-side context such as a pipeline or transaction.
 *
 * @method $this copy(string $source, string $destination, int $db = -1, bool $replace = false)
 * @method $this del(array|string $keys)
 * @method $this delex(string $key, string $flag, $flagValue)
 * @method $this digest(string $key)
 * @method $this dump($key)
 * @method $this exists($key)
 * @method $this expire($key, $seconds, string $expireOption = '')
 * @method $this expireat($key, $timestamp, string $expireOption = '')
 * @method $this expiretime(string $key)
 * @method $this keys($pattern)
 * @method $this move($key, $db)
 * @method $this object($subcommand, $key)
 * @method $this persist($key)
 * @method $this pexpire($key, $milliseconds, string $option = null)
 * @method $this pexpireat($key, $timestamp, string $option = null)
 * @method $this pttl($key)
 * @method $this randomkey()
 * @method $this rename($key, $target)
 * @method $this renamenx($key, $target)
 * @method $this scan($cursor, ?array $options = null)
 * @method $this sort($key, ?array $options = null)
 * @method $this sort_ro(string $key, ?string $byPattern = null, ?LimitOffsetCount $limit = null, array $getPatterns = [], ?string $sorting = null, bool $alpha = false)
 * @method $this ttl($key)
 * @method $this type($key)
 * @method $this append($key, $value)
 * @method $this bfadd(string $key, $item)
 * @method $this bfexists(string $key, $item)
 * @method $this bfinfo(string $key, string $modifier = '')
 * @method $this bfinsert(string $key, int $capacity = -1, float $error = -1, int $expansion = -1, bool $noCreate = false, bool $nonScaling = false, string ...$item)
 * @method $this bfloadchunk(string $key, int $iterator, $data)
 * @method $this bfmadd(string $key, ...$item)
 * @method $this bfmexists(string $key, ...$item)
 * @method $this bfreserve(string $key, float $errorRate, int $capacity, int $expansion = -1, bool $nonScaling = false)
 * @method $this bfscandump(string $key, int $iterator)
 * @method $this bitcount(string $key, $start = null, $end = null, string $index = 'byte')
 * @method $this bitop($operation, $destkey, $key)
 * @method $this bitfield($key, $subcommand, ...$subcommandArg)
 * @method $this bitfield_ro(string $key, ?array $encodingOffsetMap = null)
 * @method $this bitpos($key, $bit, $start = null, $end = null, string $index = 'byte')
 * @method $this blmpop(int $timeout, array $keys, string $modifier = 'left', int $count = 1)
 * @method $this bzpopmax(array $keys, int $timeout)
 * @method $this bzpopmin(array $keys, int $timeout)
 * @method $this bzmpop(int $timeout, array $keys, string $modifier = 'min', int $count = 1)
 * @method $this cfadd(string $key, $item)
 * @method $this cfaddnx(string $key, $item)
 * @method $this cfcount(string $key, $item)
 * @method $this cfdel(string $key, $item)
 * @method $this cfexists(string $key, $item)
 * @method $this cfloadchunk(string $key, int $iterator, $data)
 * @method $this cfmexists(string $key, ...$item)
 * @method $this cfinfo(string $key)
 * @method $this cfinsert(string $key, int $capacity = -1, bool $noCreate = false, string ...$item)
 * @method $this cfinsertnx(string $key, int $capacity = -1, bool $noCreate = false, string ...$item)
 * @method $this cfreserve(string $key, int $capacity, int $bucketSize = -1, int $maxIterations = -1, int $expansion = -1)
 * @method $this cfscandump(string $key, int $iterator)
 * @method $this cmsincrby(string $key, string|int...$itemIncrementDictionary)
 * @method $this cmsinfo(string $key)
 * @method $this cmsinitbydim(string $key, int $width, int $depth)
 * @method $this cmsinitbyprob(string $key, float $errorRate, float $probability)
 * @method $this cmsmerge(string $destination, array $sources, array $weights = [])
 * @method $this cmsquery(string $key, string ...$item)
 * @method $this decr($key)
 * @method $this decrby($key, $decrement)
 * @method $this failover(?To $to = null, bool $abort = false, int $timeout = -1)
 * @method $this fcall(string $function, array $keys, ...$args)
 * @method $this fcall_ro(string $function, array $keys, ...$args)
 * @method $this ft_list()
 * @method $this ftaggregate(string $index, string $query, ?AggregateArguments $arguments = null)
 * @method $this ftaliasadd(string $alias, string $index)
 * @method $this ftaliasdel(string $alias)
 * @method $this ftaliasupdate(string $alias, string $index)
 * @method $this ftalter(string $index, FieldInterface[] $schema, ?AlterArguments $arguments = null)
 * @method $this ftcreate(string $index, FieldInterface[] $schema, ?CreateArguments $arguments = null)
 * @method $this ftdictadd(string $dict, ...$term)
 * @method $this ftdictdel(string $dict, ...$term)
 * @method $this ftdictdump(string $dict)
 * @method $this ftdropindex(string $index, ?DropArguments $arguments = null)
 * @method $this ftexplain(string $index, string $query, ?ExplainArguments $arguments = null)
 * @method $this fthybrid(string $index, HybridSearchQuery $query)
 * @method $this ftinfo(string $index)
 * @method $this ftprofile(string $index, ProfileArguments $arguments)
 * @method $this ftsearch(string $index, string $query, ?SearchArguments $arguments = null)
 * @method $this ftspellcheck(string $index, string $query, ?SearchArguments $arguments = null)
 * @method $this ftsugadd(string $key, string $string, float $score, ?SugAddArguments $arguments = null)
 * @method $this ftsugdel(string $key, string $string)
 * @method $this ftsugget(string $key, string $prefix, ?SugGetArguments $arguments = null)
 * @method $this ftsuglen(string $key)
 * @method $this ftsyndump(string $index)
 * @method $this ftsynupdate(string $index, string $synonymGroupId, ?SynUpdateArguments $arguments = null, string ...$terms)
 * @method $this fttagvals(string $index, string $fieldName)
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
 * @method $this msetex(array $dictionary, ?string $existModifier = null, ?string $expireResolution = null, ?int $expireTTL = null)
 * @method $this msetnx(array $dictionary)
 * @method $this psetex($key, $milliseconds, $value)
 * @method $this set($key, $value, $expireResolution = null, $expireTTL = null, $flag = null, $flagValue = null)
 * @method $this setbit($key, $offset, $value)
 * @method $this setex($key, $seconds, $value)
 * @method $this setnx($key, $value)
 * @method $this setrange($key, $offset, $value)
 * @method $this strlen($key)
 * @method $this hdel($key, array $fields)
 * @method $this hexists($key, $field)
 * @method $this hexpire(string $key, int $seconds, array $fields, string $flag = null)
 * @method $this hexpireat(string $key, int $unixTimeSeconds, array $fields, string $flag = null)
 * @method $this hexpiretime(string $key, array $fields)
 * @method $this hpersist(string $key, array $fields)
 * @method $this hpexpire(string $key, int $milliseconds, array $fields, string $flag = null)
 * @method $this hpexpireat(string $key, int $unixTimeMilliseconds, array $fields, string $flag = null)
 * @method $this hpexpiretime(string $key, array $fields)
 * @method $this hget($key, $field)
 * @method $this hgetex(string $key, array $fields, string $modifier = HGETEX::NULL)
 * @method $this hgetall($key)
 * @method $this hgetdel(string $key, array $fields)
 * @method $this hincrby($key, $field, $increment)
 * @method $this hincrbyfloat($key, $field, $increment)
 * @method $this hkeys($key)
 * @method $this hlen($key)
 * @method $this hmget($key, array $fields)
 * @method $this hmset($key, array $dictionary)
 * @method $this hrandfield(string $key, int $count = 1, bool $withValues = false)
 * @method $this hscan($key, $cursor, ?array $options = null)
 * @method $this hset($key, $field, $value)
 * @method $this hsetex(string $key, array $fieldValueMap, string $setModifier = HSETEX::SET_NULL, string $ttlModifier = HSETEX::TTL_NULL, int|bool $ttlModifierValue = false)
 * @method $this hsetnx($key, $field, $value)
 * @method $this httl(string $key, array $fields)
 * @method $this hpttl(string $key, array $fields)
 * @method $this hvals($key)
 * @method $this hstrlen($key, $field)
 * @method $this jsonarrappend(string $key, string $path = '$', ...$value)
 * @method $this jsonarrindex(string $key, string $path, string $value, int $start = 0, int $stop = 0)
 * @method $this jsonarrinsert(string $key, string $path, int $index, string ...$value)
 * @method $this jsonarrlen(string $key, string $path = '$')
 * @method $this jsonarrpop(string $key, string $path = '$', int $index = -1)
 * @method $this jsonarrtrim(string $key, string $path, int $start, int $stop)
 * @method $this jsonclear(string $key, string $path = '$')
 * @method $this jsondel(string $key, string $path = '$')
 * @method $this jsonforget(string $key, string $path = '$')
 * @method $this jsonget(string $key, string $indent = '', string $newline = '', string $space = '', string ...$paths)
 * @method $this jsonnumincrby(string $key, string $path, int $value)
 * @method $this jsonmerge(string $key, string $path, string $value)
 * @method $this jsonmget(array $keys, string $path)
 * @method $this jsonmset(string ...$keyPathValue)
 * @method $this jsonobjkeys(string $key, string $path = '$')
 * @method $this jsonobjlen(string $key, string $path = '$')
 * @method $this jsonresp(string $key, string $path = '$')
 * @method $this jsonset(string $key, string $path, string $value, ?string $subcommand = null)
 * @method $this jsonstrappend(string $key, string $path, string $value)
 * @method $this jsonstrlen(string $key, string $path = '$')
 * @method $this jsontoggle(string $key, string $path)
 * @method $this jsontype(string $key, string $path = '$')
 * @method $this blmove(string $source, string $destination, string $where, string $to, int $timeout)
 * @method $this blpop(array|string $keys, $timeout)
 * @method $this brpop(array|string $keys, $timeout)
 * @method $this brpoplpush($source, $destination, $timeout)
 * @method $this lcs(string $key1, string $key2, bool $len = false, bool $idx = false, int $minMatchLen = 0, bool $withMatchLen = false)
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
 * @method $this sintercard(array $keys, int $limit = 0)
 * @method $this sinterstore($destination, array|string $keys)
 * @method $this sismember($key, $member)
 * @method $this smembers($key)
 * @method $this smismember(string $key, string ...$members)
 * @method $this smove($source, $destination, $member)
 * @method $this spop($key, $count = null)
 * @method $this srandmember($key, $count = null)
 * @method $this srem($key, $member)
 * @method $this sscan($key, $cursor, ?array $options = null)
 * @method $this ssubscribe(string ...$shardChannels)
 * @method $this subscribe(string ...$channels)
 * @method $this sunsubscribe(?string ...$shardChannels = null)
 * @method $this sunion(array|string $keys)
 * @method $this sunionstore($destination, array|string $keys)
 * @method $this tdigestadd(string $key, float ...$value)
 * @method $this tdigestbyrank(string $key, int ...$rank)
 * @method $this tdigestbyrevrank(string $key, int ...$reverseRank)
 * @method $this tdigestcdf(string $key, int ...$value)
 * @method $this tdigestcreate(string $key, int $compression = 0)
 * @method $this tdigestinfo(string $key)
 * @method $this tdigestmax(string $key)
 * @method $this tdigestmerge(string $destinationKey, array $sourceKeys, int $compression = 0, bool $override = false)
 * @method $this tdigestquantile(string $key, float ...$quantile)
 * @method $this tdigestmin(string $key)
 * @method $this tdigestrank(string $key, ...$value)
 * @method $this tdigestreset(string $key)
 * @method $this tdigestrevrank(string $key, float ...$value)
 * @method $this tdigesttrimmed_mean(string $key, float $lowCutQuantile, float $highCutQuantile)
 * @method $this topkadd(string $key, ...$items)
 * @method $this topkincrby(string $key, ...$itemIncrement)
 * @method $this topkinfo(string $key)
 * @method $this topklist(string $key, bool $withCount = false)
 * @method $this topkquery(string $key, ...$items)
 * @method $this topkreserve(string $key, int $topK, int $width = 8, int $depth = 7, float $decay = 0.9)
 * @method $this tsadd(string $key, int $timestamp, string|float $value, ?AddArguments $arguments = null)
 * @method $this tsalter(string $key, ?TSAlterArguments $arguments = null)
 * @method $this tscreate(string $key, ?TSCreateArguments $arguments = null)
 * @method $this tscreaterule(string $sourceKey, string $destKey, string $aggregator, int $bucketDuration, int $alignTimestamp = 0)
 * @method $this tsdecrby(string $key, float $value, ?DecrByArguments $arguments = null)
 * @method $this tsdel(string $key, int $fromTimestamp, int $toTimestamp)
 * @method $this tsdeleterule(string $sourceKey, string $destKey)
 * @method $this tsget(string $key, ?GetArguments $arguments = null)
 * @method $this tsincrby(string $key, float $value, ?IncrByArguments $arguments = null)
 * @method $this tsinfo(string $key, ?InfoArguments $arguments = null)
 * @method $this tsmadd(mixed ...$keyTimestampValue)
 * @method $this tsmget(MGetArguments $arguments, string ...$filterExpression)
 * @method $this tsmrange($fromTimestamp, $toTimestamp, MRangeArguments $arguments)
 * @method $this tsmrevrange($fromTimestamp, $toTimestamp, MRangeArguments $arguments)
 * @method $this tsqueryindex(string ...$filterExpression)
 * @method $this tsrange(string $key, $fromTimestamp, $toTimestamp, ?RangeArguments $arguments = null)
 * @method $this tsrevrange(string $key, $fromTimestamp, $toTimestamp, ?RangeArguments $arguments = null)
 * @method $this xack(string $key, string $group, string ...$id)
 * @method $this xackdel(string $key, string $group, string $mode, array $ids)
 * @method $this xadd(string $key, array $dictionary, string $id = '*', array $options = null)
 * @method $this xautoclaim(string $key, string $group, string $consumer, int $minIdleTime, string $start, ?int $count = null, bool $justId = false)
 * @method $this xclaim(string $key, string $group, string $consumer, int $minIdleTime, string|array $ids, ?int $idle = null, ?int $time = null, ?int $retryCount = null, bool $force = false, bool $justId = false, ?string $lastId = null)
 * @method $this xcfgset(string $key, ?int $duration = null, ?int $maxsize = null)
 * @method $this xdel(string $key, string ...$id)
 * @method $this xdelex(string $key, string $mode, array $ids)
 * @method $this xlen(string $key)
 * @method $this xpending(string $key, string $group, ?int $minIdleTime = null, ?string $start = null, ?string $end = null, ?int $count = null, ?string $consumer = null)
 * @method $this xrevrange(string $key, string $end, string $start, ?int $count = null)
 * @method $this xrange(string $key, string $start, string $end, ?int $count = null)
 * @method $this xread(int $count = null, int $block = null, array $streams = null, string ...$id)
 * @method $this xreadgroup(string $group, string $consumer, ?int $count = null, ?int $blockMs = null, bool $noAck = false, string ...$keyOrId)
 * @method $this xreadgroup_claim(string $group, string $consumer, array $keyIdDict, ?int $count = null, ?int $blockMs = null, bool $noAck = false, ?int $claim = null)
 * @method $this xsetid(string $key, string $lastId, ?int $entriesAdded = null, ?string $maxDeleteId = null)
 * @method $this xtrim(string $key, array|string $strategy, string $threshold, array $options = null)
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
 * @method $this zrange($key, $start, $stop, ?array $options = null)
 * @method $this zrangebyscore($key, $min, $max, ?array $options = null)
 * @method $this zrangestore(string $destination, string $source, int|string $min, string|int $max, string|bool $by = false, bool $reversed = false, bool $limit = false, int $offset = 0, int $count = 0)
 * @method $this zrank($key, $member)
 * @method $this zrem($key, $member)
 * @method $this zremrangebyrank($key, $start, $stop)
 * @method $this zremrangebyscore($key, $min, $max)
 * @method $this zrevrange($key, $start, $stop, ?array $options = null)
 * @method $this zrevrangebyscore($key, $max, $min, ?array $options = null)
 * @method $this zrevrank($key, $member)
 * @method $this zunion(array $keys, int[] $weights = [], string $aggregate = 'sum', bool $withScores = false)
 * @method $this zunionstore(string $destination, array $keys, int[] $weights = [], string $aggregate = 'sum')
 * @method $this zscore($key, $member)
 * @method $this zscan($key, $cursor, ?array $options = null)
 * @method $this zrangebylex($key, $start, $stop, ?array $options = null)
 * @method $this zrevrangebylex($key, $start, $stop, ?array $options = null)
 * @method $this zremrangebylex($key, $min, $max)
 * @method $this zlexcount($key, $min, $max)
 * @method $this pexpiretime(string $key)
 * @method $this pfadd($key, array $elements)
 * @method $this pfmerge($destinationKey, array|string $sourceKeys)
 * @method $this pfcount(array|string $keys)
 * @method $this pubsub($subcommand, $argument)
 * @method $this publish($channel, $message)
 * @method $this discard()
 * @method $this exec()
 * @method $this multi()
 * @method $this unwatch()
 * @method $this waitaof(int $numLocal, int $numReplicas, int $timeout)
 * @method $this unsubscribe(string ...$channels)
 * @method $this vadd(string $key, string|array $vector, string $elem, int $dim = null, bool $cas = false, string $quant = VADD::QUANT_DEFAULT, ?int $BEF = null, string|array $attributes = null, int $numlinks = null)
 * @method $this vcard(string $key)
 * @method $this vdim(int $key)
 * @method $this vemb(string $key, string $elem, bool $raw = false)
 * @method $this vgetattr(string $key, string $elem, bool $asJson = false)
 * @method $this vinfo(string $key)
 * @method $this vlinks(string $key, string $elem, bool $withScores = false)
 * @method $this vrandmember(string $key, int $count = null)
 * @method $this vrange(string $key, string $start, string $end, int $count = null)
 * @method $this vrem(string $key, string $elem)
 * @method $this vsetattr(string $key, string $elem, string|array $attributes)
 * @method $this vsim(string $key, string|array $vectorOrElem, bool $isElem = false, bool $withScores = false, int $count = null, float $epsilon = null, int $ef = null, string $filter = null, int $filterEf = null, bool $truth = false, bool $noThread = false)
 * @method $this watch($key)
 * @method $this eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method $this eval_ro(string $script, array $keys, ...$argument)
 * @method $this evalsha($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
 * @method $this evalsha_ro(string $sha1, array $keys, ...$argument)
 * @method $this script($subcommand, $argument = null)
 * @method $this shutdown(?bool $noSave = null, bool $now = false, bool $force = false, bool $abort = false)
 * @method $this auth($password)
 * @method $this echo($message)
 * @method $this ping($message = null)
 * @method $this select($database)
 * @method $this bgrewriteaof()
 * @method $this bgsave()
 * @method $this config($subcommand, $argument = null)
 * @method $this dbsize()
 * @method $this flushall()
 * @method $this flushdb()
 * @method $this info(string ...$section = null)
 * @method $this lastsave()
 * @method $this save()
 * @method $this slaveof($host, $port)
 * @method $this slowlog($subcommand, $argument = null)
 * @method $this spublish(string $shardChannel, string $message)
 * @method $this time()
 * @method $this command($subcommand, $argument = null)
 * @method $this geoadd($key, $longitude, $latitude, $member)
 * @method $this geohash($key, array $members)
 * @method $this geopos($key, array $members)
 * @method $this geodist($key, $member1, $member2, $unit = null)
 * @method $this georadius($key, $longitude, $latitude, $radius, $unit, ?array $options = null)
 * @method $this georadiusbymember($key, $member, $radius, $unit, ?array $options = null)
 * @method $this geosearch(string $key, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $withCoord = false, bool $withDist = false, bool $withHash = false)
 * @method $this geosearchstore(string $destination, string $source, FromInterface $from, ByInterface $by, ?string $sorting = null, int $count = -1, bool $any = false, bool $storeDist = false)
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
