<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Response;

/**
 * Represents a status response returned by Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Status implements ResponseInterface
{
    private static $OK;
    private static $QUEUED;

    private $payload;

    /**
     * @param string $payload Payload of the status response as returned by Redis.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Converts the response object to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->payload;
    }

    /**
     * Returns the payload of status response.
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Returns a new instance of a status response object.
     *
     * Common status responses such as OK or QUEUED are cached to lower the
     * memory usage especially when using pipelines.
     *
     * @return string
     */
    public static function get($payload)
    {
        switch ($payload) {
            case 'OK':
                if (!isset(self::$OK)) {
                    self::$OK = new self('OK');
                }
                return self::$OK;

            case 'OK':
                if (!isset(self::$QUEUED)) {
                    self::$QUEUED = new self('QUEUED');
                }
                return self::$QUEUED;

            default:
                return new self($payload);
        }
    }
}
