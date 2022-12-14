<?php

namespace Predis\Command\Traits\With;

use UnexpectedValueException;

trait BaseWith
{
    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);
        $argumentPositionOffset = $this->getArgumentPositionOffset();

        if (
            $argumentPositionOffset >= $argumentsLength
            || false === $arguments[$argumentPositionOffset]
        ) {
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[$argumentPositionOffset];

        if (true === $argument) {
            $argument = $this->getKeyword();
        } else {
            throw new UnexpectedValueException("Wrong {$argument} argument type");
        }

        $argumentsBefore = array_slice($arguments, 0, $argumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  $argumentPositionOffset + 1);

        parent::setArguments(array_merge($argumentsBefore, [$argument], $argumentsAfter));
    }

    /**
     * @return int
     */
    abstract public function getArgumentPositionOffset(): int;

    /**
     * @return string
     */
    abstract public function getKeyword(): string;
}
