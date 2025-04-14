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

namespace Predis\Command\Argument\Geospatial;

class ByRadius extends AbstractBy
{
    private const KEYWORD = 'BYRADIUS';

    /**
     * @var int
     */
    private $radius;

    public function __construct(int $radius, string $unit)
    {
        $this->radius = $radius;
        $this->setUnit($unit);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->radius, $this->unit];
    }
}
