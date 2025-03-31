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

use Predis\Response\ResponseInterface;

interface PushResponseInterface extends ResponseInterface
{
    public const PUB_SUB_DATA_TYPE = 'pubsub';
    public const MONITOR_DATA_TYPE = 'monitor';
    public const INVALIDATE_DATA_TYPE = 'invalidate';
    public const MESSAGE_DATA_TYPE = 'message';

    /**
     * Returns PUSH notification data type.
     *
     * @return string
     */
    public function getDataType(): string;

    /**
     * Returns PUSH notification payload.
     *
     * @return array
     */
    public function getPayload(): array;
}
