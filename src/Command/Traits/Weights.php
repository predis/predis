<?php

namespace Predis\Command\Traits;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait Weights
{
    /**
     * @var string
     */
    private static $weightsModifier = 'WEIGHTS';

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$weightsArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if (!is_array($arguments[static::$weightsArgumentPositionOffset])) {
            throw new UnexpectedValueException('Wrong weights argument type');
        }

        $weightsArray = $arguments[static::$weightsArgumentPositionOffset];

        if (empty($weightsArray)) {
            unset($arguments[static::$weightsArgumentPositionOffset]);
            parent::setArguments($arguments);
            return;
        }

        $argumentsBefore = array_slice($arguments, 0, static::$weightsArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments, static::$weightsArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$weightsModifier],
            $weightsArray,
            $argumentsAfter
        ));
    }
}
