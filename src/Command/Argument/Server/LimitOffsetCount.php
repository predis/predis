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

class LimitOffsetCount implements LimitInterface
{
    private const KEYWORD = 'LIMIT';

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $count;

    public function __construct(int $offset, int $count)
    {
        $this->offset = $offset;
        $this->count = $count;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->offset, $this->count];
    }
}
