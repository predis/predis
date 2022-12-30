<?php

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
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->longitude, $this->latitude];
    }
}
