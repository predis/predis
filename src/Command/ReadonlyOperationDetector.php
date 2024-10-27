<?php

declare(strict_types=1);

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

class ReadonlyOperationDetector implements ReadonlyOperationDetectorInterface
{
    private $readonlyOperations;

    /**
     * @param array|null $readonlyOperations
     */
    public function __construct(?array $readonlyOperations = null)
    {
        $this->readonlyOperations = $readonlyOperations ?: $this->getReadOnlyOperations();
    }

    /**
     * {@inheritdoc}
     */
    public function detect(CommandInterface $command): bool
    {
        $id = $command->getId();

        if (isset($this->readonlyOperations[$id])) {
            if (true === $readonly = $this->readonlyOperations[$id]) {
                return true;
            }

            return call_user_func($readonly, $command);
        }

        return false;
    }

    /**
     * Returns the default list of commands performing read-only operations.
     *
     * @return array
     */
    protected function getReadOnlyOperations(): array
    {
        return [
            'EXISTS' => true,
            'TYPE' => true,
            'KEYS' => true,
            'SCAN' => true,
            'RANDOMKEY' => true,
            'TTL' => true,
            'GET' => true,
            'MGET' => true,
            'SUBSTR' => true,
            'STRLEN' => true,
            'GETRANGE' => true,
            'GETBIT' => true,
            'LLEN' => true,
            'LRANGE' => true,
            'LINDEX' => true,
            'SCARD' => true,
            'SISMEMBER' => true,
            'SINTER' => true,
            'SUNION' => true,
            'SDIFF' => true,
            'SMEMBERS' => true,
            'SSCAN' => true,
            'SRANDMEMBER' => true,
            'ZRANGE' => true,
            'ZREVRANGE' => true,
            'ZRANGEBYSCORE' => true,
            'ZREVRANGEBYSCORE' => true,
            'ZCARD' => true,
            'ZSCORE' => true,
            'ZCOUNT' => true,
            'ZRANK' => true,
            'ZREVRANK' => true,
            'ZSCAN' => true,
            'ZLEXCOUNT' => true,
            'ZRANGEBYLEX' => true,
            'ZREVRANGEBYLEX' => true,
            'HGET' => true,
            'HMGET' => true,
            'HEXISTS' => true,
            'HLEN' => true,
            'HKEYS' => true,
            'HVALS' => true,
            'HGETALL' => true,
            'HSCAN' => true,
            'HSTRLEN' => true,
            'PING' => true,
            'AUTH' => true,
            'SELECT' => true,
            'ECHO' => true,
            'QUIT' => true,
            'OBJECT' => true,
            'BITCOUNT' => true,
            'BITPOS' => true,
            'TIME' => true,
            'PFCOUNT' => true,
            'BITFIELD' => [$this, 'isBitfieldReadOnly'],
            'GEOHASH' => true,
            'GEOPOS' => true,
            'GEODIST' => true,
            'GEORADIUS' => [$this, 'isGeoradiusReadOnly'],
            'GEORADIUSBYMEMBER' => [$this, 'isGeoradiusReadOnly'],
            'GEOSEARCH' => true,
        ];
    }

    /**
     * Checks if BITFIELD performs a read-only operation by looking for certain
     * SET and INCRYBY modifiers in the arguments array of the command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return bool
     */
    protected function isBitfieldReadOnly(CommandInterface $command): bool
    {
        $arguments = $command->getArguments();
        $argc = count($arguments);

        if ($argc >= 2) {
            for ($i = 1; $i < $argc; ++$i) {
                $argument = strtoupper($arguments[$i]);
                if ($argument === 'SET' || $argument === 'INCRBY') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks if a GEORADIUS command is a readable operation by parsing the
     * arguments array of the specified command instance.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return bool
     */
    protected function isGeoradiusReadOnly(CommandInterface $command): bool
    {
        $arguments = $command->getArguments();
        $argc = count($arguments);
        $startIndex = $command->getId() === 'GEORADIUS' ? 5 : 4;

        if ($argc > $startIndex) {
            for ($i = $startIndex; $i < $argc; ++$i) {
                $argument = strtoupper($arguments[$i]);
                if ($argument === 'STORE' || $argument === 'STOREDIST') {
                    return false;
                }
            }
        }

        return true;
    }
}
