<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;
use UnexpectedValueException;

class HGETEX extends RedisCommand
{
    public const NULL = '';
    public const EX = 'ex';
    public const PX = 'px';
    public const EXAT = 'exat';
    public const PXAT = 'pxat';
    public const PERSIST = 'persist';

    /**
     * @var string[]
     */
    private static $modifierEnum = [
        self::EX => 'EX',
        self::PX => 'PX',
        self::EXAT => 'EXAT',
        self::PXAT => 'PXAT',
        self::PERSIST => 'PERSIST',
    ];

    public function getId()
    {
        return 'HGETEX';
    }

    public function setArguments(array $arguments)
    {
        $processedArguments = [$arguments[0]];

        // Only required arguments
        if (!array_key_exists(2, $arguments) || $arguments[2] == '') {
            array_push($processedArguments, 'FIELDS', count($arguments[1]));
            $processedArguments = array_merge($processedArguments, $arguments[1]);
            parent::setArguments($processedArguments);

            return;
        }

        if (!in_array(strtoupper($arguments[2]), self::$modifierEnum)) {
            $enumValues = implode(', ', array_keys(self::$modifierEnum));
            throw new UnexpectedValueException("Modifier argument accepts only: {$enumValues} values");
        }

        // PERSIST requires no additional value
        if (strtoupper($arguments[2]) === self::$modifierEnum['persist']) {
            $processedArguments[] = self::$modifierEnum['persist'];
            array_push($processedArguments, 'FIELDS', count($arguments[1]));
            $processedArguments = array_merge($processedArguments, $arguments[1]);
            parent::setArguments($processedArguments);

            return;
        }

        if (!array_key_exists(3, $arguments) || !is_int($arguments[3])) {
            throw new UnexpectedValueException('Modifier value is missing or incorrect type');
        }

        // Order matters so FIELDS should be at the end
        array_push($processedArguments, self::$modifierEnum[strtolower($arguments[2])], $arguments[3], 'FIELDS', count($arguments[1]));
        $processedArguments = array_merge($processedArguments, $arguments[1]);

        parent::setArguments($processedArguments);
    }
}
