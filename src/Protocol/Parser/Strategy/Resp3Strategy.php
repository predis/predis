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

namespace Predis\Protocol\Parser\Strategy;

class Resp3Strategy extends Resp2Strategy
{
    public const TYPE_BLOB_ERROR = 'blobError';
    public const TYPE_VERBATIM_STRING = 'verbatimString';
    public const TYPE_MAP = 'map';
    public const TYPE_SET = 'set';
    public const TYPE_PUSH = 'push';

    /**
     * Verbatim string offset to skip file extension bytes.
     */
    public const VERBATIM_STRING_EXTENSION_OFFSET = 4;

    /**
     * @var string[]
     */
    protected $resp3TypeCallbacks = [
        '_' => 'parseNull',
        ',' => 'parseDouble',
        '#' => 'parseBoolean',
        '!' => 'parseBlobError',
        '=' => 'parseVerbatimString',
        '(' => 'parseBigNumber',
        '%' => 'parseMap',
        '~' => 'parseSet',
        '>' => 'parsePush',
    ];

    public function __construct()
    {
        $this->typeCallbacks += $this->resp3TypeCallbacks;
    }

    /**
     * Parse null RESP3 type.
     *
     * @return null
     */
    protected function parseNull(string $string)
    {
        return null;
    }

    /**
     * Parse double RESP3 type.
     *
     * @param  string $string
     * @return float
     */
    protected function parseDouble(string $string): float
    {
        if ($string === 'inf' || $string === '-inf') {
            return INF;
        }

        return (float) $string;
    }

    /**
     * Parse boolean RESP3 type.
     *
     * @param  string $string
     * @return bool
     */
    protected function parseBoolean(string $string): bool
    {
        return $string === 't';
    }

    /**
     * Parse blob error RESP3 type.
     *
     * @param  string $string
     * @return array
     */
    protected function parseBlobError(string $string): array
    {
        return [
            'type' => self::TYPE_BLOB_ERROR,
            'value' => (int) $string,
        ];
    }

    /**
     * Parse verbatim string RESP3 type.
     *
     * @param  string $string
     * @return array
     */
    protected function parseVerbatimString(string $string): array
    {
        return [
            'type' => self::TYPE_VERBATIM_STRING,
            'value' => (int) $string,
            'offset' => self::VERBATIM_STRING_EXTENSION_OFFSET,
        ];
    }

    /**
     * Parse big number RESP3 type.
     * Depends on PHP environment returns float on numbers that reaches max integer limit.
     *
     * @param  string    $string
     * @return int|float
     */
    protected function parseBigNumber(string $string)
    {
        if (bccomp($string, PHP_INT_MAX) === 1) {
            return (float) $string;
        }

        return $this->parseInteger($string);
    }

    /**
     * Parse map RESP3 type.
     *
     * @param  string $string
     * @return array
     */
    protected function parseMap(string $string): array
    {
        return [
            'type' => self::TYPE_MAP,
            'value' => (int) $string,
        ];
    }

    /**
     * Parse set RESP3 type.
     *
     * @param  string $string
     * @return array
     */
    protected function parseSet(string $string): array
    {
        return [
            'type' => self::TYPE_SET,
            'value' => (int) $string,
        ];
    }

    /**
     * Parse push RESP3 type.
     *
     * @param  string $string
     * @return array
     */
    protected function parsePush(string $string): array
    {
        return [
            'type' => self::TYPE_PUSH,
            'value' => (int) $string,
        ];
    }
}
