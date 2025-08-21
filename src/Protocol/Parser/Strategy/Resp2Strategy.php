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

use Predis\Protocol\Parser\UnexpectedTypeException;
use Predis\Response\Error;
use Predis\Response\ErrorInterface;
use Predis\Response\Status as StatusResponse;

class Resp2Strategy implements ParserStrategyInterface
{
    public const TYPE_ARRAY = 'array';
    public const TYPE_BULK_STRING = 'bulkString';

    /**
     * Callbacks to process given RESP type.
     *
     * @var string[]
     */
    protected $typeCallbacks = [
        '+' => 'parseSimpleString',
        '-' => 'parseError',
        ':' => 'parseInteger',
        '*' => 'parseArray',
        '$' => 'parseBulkString',
    ];

    /**
     * RESP 2 Status responses.
     *
     * @var string[]
     */
    protected $statusResponse = [
        'OK',
        'QUEUED',
        'NOKEY',
        'PONG',
    ];

    /**
     * {@inheritDoc}
     */
    public function parseData(string $data)
    {
        $type = $data[0];
        $payload = substr($data, 1, -2);

        if (!array_key_exists($type, $this->typeCallbacks)) {
            throw new UnexpectedTypeException($type, 'Unexpected data type given.');
        }

        $callback = $this->typeCallbacks[$type];

        return $this->$callback($payload);
    }

    /**
     * Parse simple string RESP type.
     *
     * @param  string                $string
     * @return StatusResponse|string
     */
    protected function parseSimpleString(string $string)
    {
        if (in_array($string, $this->statusResponse)) {
            return StatusResponse::get($string);
        }

        return $string;
    }

    /**
     * Parse error RESP type.
     *
     * @param  string         $string
     * @return ErrorInterface
     */
    protected function parseError(string $string): ErrorInterface
    {
        return new Error($string);
    }

    /**
     * Parse integer RESP type.
     *
     * @param  string $string
     * @return int
     */
    protected function parseInteger(string $string): int
    {
        return (int) $string;
    }

    /**
     * Parse array RESP type.
     *
     * @param  string $string
     * @return array
     */
    protected function parseArray(string $string): ?array
    {
        $count = (int) $string;

        if ($count === -1) {
            return null;
        }

        return [
            'type' => self::TYPE_ARRAY,
            'value' => $count,
        ];
    }

    /**
     * Parse bulk string RESP type.
     *
     * @param  string $string
     * @return array
     */
    protected function parseBulkString(string $string): ?array
    {
        $size = (int) $string;

        if ($size === -1) {
            return null;
        }

        return [
            'type' => self::TYPE_BULK_STRING,
            'value' => $size,
        ];
    }
}
