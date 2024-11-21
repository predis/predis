<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Geospatial;

class FromMember implements FromInterface
{
    private const KEYWORD = 'FROMMEMBER';

    /**
     * @var string
     */
    private $member;

    public function __construct(string $member)
    {
        $this->member = $member;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->member];
    }
}
