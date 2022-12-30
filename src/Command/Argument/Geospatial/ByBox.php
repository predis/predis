<?php

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
     * @inheritDoc
     */
    public function toArray(): array
    {
        return [self::KEYWORD, $this->width, $this->height, $this->unit];
    }
}
