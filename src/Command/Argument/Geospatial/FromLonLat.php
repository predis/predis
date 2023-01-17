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

class FromLonLat implements FromInterface
{
    private const KEYWORD = 'FROMLONLAT';

    /**
     * @var float
     */
    private $longitude;

    /**
     * @var float
     */
    private $latitude;

    public function __construct(float $longitude, float $latitude)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->longitude, $this->latitude];
    }
}
