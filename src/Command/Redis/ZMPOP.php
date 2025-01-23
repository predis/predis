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
use Predis\Command\Traits\Count;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\MinMaxModifier;

/**
 * @see https://redis.io/commands/zmpop/
 *
 * Pops one or more elements, that are member-score pairs,
 * from the first non-empty sorted set in the provided list of key names.
 */
class ZMPOP extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Count {
        Count::setArguments as setCount;
    }
    use MinMaxModifier;

    protected static $keysArgumentPositionOffset = 0;
    protected static $countArgumentPositionOffset = 2;
    protected static $modifierArgumentPositionOffset = 1;

    public function getId()
    {
        return 'ZMPOP';
    }

    public function setArguments(array $arguments)
    {
        $this->setCount($arguments);
        $arguments = $this->getArguments();

        $this->resolveModifier(static::$modifierArgumentPositionOffset, $arguments);

        $this->setKeys($arguments);
        $arguments = $this->getArguments();

        parent::setArguments($arguments);
    }

    public function parseResponse($data)
    {
        $key = array_shift($data);

        if (null === $key) {
            return [$key];
        }

        $data = $data[0];
        $parsedData = [];

        for ($i = 0, $iMax = count($data); $i < $iMax; $i++) {
            for ($j = 0, $jMax = count($data[$i]); $j < $jMax; ++$j) {
                if ($data[$i][$j + 1] ?? false) {
                    $parsedData[$data[$i][$j]] = $data[$i][++$j];
                }
            }
        }

        return array_combine([$key], [$parsedData]);
    }
}
