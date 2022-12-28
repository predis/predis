<?php

namespace Predis\Command\Argument;

/**
 * Allows to use object-oriented approach to handle complex conditional arguments.
 */
interface ArrayableArgument
{
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array;
}
