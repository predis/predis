<?php

namespace Predis\Command\Traits\Json;

use Predis\Command\Command;
use UnexpectedValueException;

/**
 * @mixin Command
 */
trait NxXxSubcommand
{
    /**
     * @var string[]
     */
    private static $subcommandEnum = [
        'nx' => 'NX',
        'xx' => 'XX',
    ];

    public function setArguments(array $arguments)
    {
        $argumentsLength = count($arguments);

        if (static::$nxXxArgumentPositionOffset >= $argumentsLength) {
            parent::setArguments($arguments);
            return;
        }

        if (null === $arguments[static::$nxXxArgumentPositionOffset]) {
            array_splice($arguments, static::$nxXxArgumentPositionOffset, 1, [false]);
            parent::setArguments($arguments);
            return;
        }

        $argument = $arguments[static::$nxXxArgumentPositionOffset];

        if (!in_array(strtoupper($argument), self::$subcommandEnum, true)) {
            $enumValues = implode(', ', array_keys(self::$subcommandEnum));
            throw new UnexpectedValueException("Subcommand argument accepts only: {$enumValues} values");
        }

        $argumentsBefore = array_slice($arguments, 0, static::$nxXxArgumentPositionOffset);
        $argumentsAfter = array_slice($arguments,  static::$nxXxArgumentPositionOffset + 1);

        parent::setArguments(array_merge(
            $argumentsBefore,
            [self::$subcommandEnum[strtolower($argument)]],
            $argumentsAfter
        ));
    }
}
