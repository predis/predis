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

namespace Predis\Command\Argument\Stream;

use Predis\Command\Argument\ArrayableArgument;

class XClaimOptions implements ArrayableArgument
{
    private $options = [];

    public function __construct(
        bool $justId = false,
        ?int $idleMs = null,
        ?int $timeMs = null,
        ?int $retryCount = null,
        bool $force = false,
        ?string $lastId = null
    ) {
        if (null !== $idleMs) {
            $this->options[] = 'IDLE';
            $this->options[] = $idleMs;
        }
        if (null !== $timeMs) {
            $this->options[] = 'TIME';
            $this->options[] = $timeMs;
        }
        if (null !== $retryCount) {
            $this->options[] = 'RETRYCOUNT';
            $this->options[] = $retryCount;
        }
        if ($force) {
            $this->options[] = 'FORCE';
        }
        if ($justId) {
            $this->options[] = 'JUSTID';
        }
        if (null !== $lastId) {
            $this->options[] = 'LASTID';
            $this->options[] = $lastId;
        }
    }

    public function toArray(): array
    {
        return $this->options;
    }
}
