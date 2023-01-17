<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Server;

use Predis\Command\Argument\ArrayableArgument;

class To implements ArrayableArgument
{
    private const KEYWORD = 'TO';
    private const FORCE_KEYWORD = 'FORCE';

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var bool
     */
    private $isForce;

    public function __construct(string $host, int $port, bool $isForce = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->isForce = $isForce;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $arguments = [self::KEYWORD, $this->host, $this->port];

        if ($this->isForce) {
            $arguments[] = self::FORCE_KEYWORD;
        }

        return $arguments;
    }
}
