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

namespace Predis\Consumer\Push;

use ArrayAccess;
use ReturnTypeWillChange;

class PushResponse implements PushResponseInterface, ArrayAccess
{
    /**
     * @var array
     */
    private $response;

    public function __construct(array $serverResponse)
    {
        $this->response = $serverResponse;
    }

    /**
     * {@inheritDoc}
     * @throws PushNotificationException
     */
    public function getDataType(): string
    {
        if (!isset($this->response[0])) {
            throw new PushNotificationException('Invalid server response');
        }

        return $this->response[0];
    }

    /**
     * {@inheritDoc}
     */
    public function getPayload(): array
    {
        return array_slice($this->response, 1);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->response[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->response[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->response[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->response[$offset]);
    }
}
