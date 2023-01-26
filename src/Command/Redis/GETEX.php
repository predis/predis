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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class GETEX extends RedisCommand
{
    /**
     * @var string[]
     */
    private static $modifierEnum = [
        'ex' => 'EX',
        'px' => 'PX',
        'exat' => 'EXAT',
        'pxat' => 'PXAT',
        'persist' => 'PERSIST',
    ];

    public function getId()
    {
        return 'GETEX';
    }

    public function setArguments(array $arguments)
    {
        if (!array_key_exists(1, $arguments) || $arguments[1] === '') {
            parent::setArguments([$arguments[0]]);

            return;
        }

        if (!in_array(strtoupper($arguments[1]), self::$modifierEnum)) {
            $enumValues = implode(', ', array_keys(self::$modifierEnum));
            throw new UnexpectedValueException("Modifier argument accepts only: {$enumValues} values");
        }

        if ($arguments[1] === 'persist') {
            parent::setArguments([$arguments[0], self::$modifierEnum[$arguments[1]]]);

            return;
        }

        $arguments[1] = self::$modifierEnum[$arguments[1]];

        if (!array_key_exists(2, $arguments)) {
            throw new UnexpectedValueException('You should provide value for current modifier');
        }

        parent::setArguments($arguments);
    }
}
