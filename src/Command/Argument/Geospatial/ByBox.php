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

namespace Predis\Command\Argument\Geospatial;

class ByBox extends AbstractBy
{
    private const KEYWORD = 'BYBOX';

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    public function __construct(int $width, int $height, string $unit)
    {
        $this->width = $width;
        $this->height = $height;
        $this->setUnit($unit);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->width, $this->height, $this->unit];
    }
}
