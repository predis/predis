<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Json;

use Predis\Command\PrefixableCommand as RedisCommand;
use Predis\Command\Traits\Json\NxXxArgument;
use UnexpectedValueException;

/**
 * @see https://redis.io/commands/json.set/
 *
 * Set the JSON value at path in key
 */
class JSONSET extends RedisCommand
{
    use NxXxArgument {
        setArguments as setSubcommand;
    }

    protected static $nxXxArgumentPositionOffset = 3;

    /**
     * @var string[]
     */
    private static $fphaEnum = [
        'bf16' => 'BF16',
        'fp16' => 'FP16',
        'fp32' => 'FP32',
        'fp64' => 'FP64',
    ];

    public function getId()
    {
        return 'JSON.SET';
    }

    public function setArguments(array $arguments)
    {
        $fpha = null;

        if (isset($arguments[4])) {
            $fpha = $arguments[4];
            array_splice($arguments, 4, 1);
        }

        if ($fpha !== null && !array_key_exists(strtolower($fpha), self::$fphaEnum)) {
            $enumValues = implode(', ', array_keys(self::$fphaEnum));
            throw new UnexpectedValueException("FPHA argument accepts only: {$enumValues} values");
        }

        $this->setSubcommand($arguments);
        $this->filterArguments();

        if ($fpha !== null) {
            $currentArgs = $this->getArguments();
            $currentArgs[] = 'FPHA';
            $currentArgs[] = self::$fphaEnum[strtolower($fpha)];
            parent::setArguments($currentArgs);
        }
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
