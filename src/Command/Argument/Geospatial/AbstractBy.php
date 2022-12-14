<?php

namespace Predis\Command\Argument\Geospatial;

use UnexpectedValueException;

abstract class AbstractBy implements ByInterface
{
    /**
     * @var string[]
     */
    private static $unitEnum = ['M', 'KM', 'FT', 'MI'];

    /**
     * @var string
     */
    protected $unit;

    /**
     * @inheritDoc
     */
    abstract public function toArray(): array;

    /**
     * @param string $unit
     * @return void
     */
    protected function setUnit(string $unit): void
    {
        if (!in_array(strtoupper($unit), self::$unitEnum, true)) {
            throw new UnexpectedValueException('Wrong value given for unit');
        }

        $this->unit = $unit;
    }
}
