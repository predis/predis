<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Observability;

/**
 * Named groups of metrics that can be enabled independently.
 */
final class MetricGroup
{
    public const RESILIENCY = 'resiliency';
    public const CONNECTION_BASIC = 'connection-basic';
    public const CONNECTION_ADVANCED = 'connection-advanced';
    public const COMMAND = 'command';
    public const STREAMING = 'streaming';
    public const PUBSUB = 'pubsub';

    public const ALL = [
        self::RESILIENCY,
        self::CONNECTION_BASIC,
        self::CONNECTION_ADVANCED,
        self::COMMAND,
        self::STREAMING,
        self::PUBSUB,
    ];
}
