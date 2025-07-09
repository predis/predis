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

namespace Predis\Command\Redis\BloomFilter;

use Predis\Command\PrefixableCommand as RedisCommand;
use UnexpectedValueException;

/**
 * @see https://redis.io/commands/bf.info/
 *
 * Return information about key filter.
 */
class BFINFO extends RedisCommand
{
    /**
     * @var string[]
     */
    private $modifierEnum = [
        'capacity' => 'CAPACITY',
        'size' => 'SIZE',
        'filters' => 'FILTERS',
        'items' => 'ITEMS',
        'expansion' => 'EXPANSION',
    ];

    public function getId()
    {
        return 'BF.INFO';
    }

    public function setArguments(array $arguments)
    {
        if (isset($arguments[1])) {
            $modifier = array_pop($arguments);

            if ($modifier === '') {
                parent::setArguments($arguments);

                return;
            }

            if (!in_array(strtoupper($modifier), $this->modifierEnum)) {
                $enumValues = implode(', ', array_keys($this->modifierEnum));
                throw new UnexpectedValueException("Argument accepts only: {$enumValues} values");
            }

            $arguments[] = $this->modifierEnum[strtolower($modifier)];
        }

        parent::setArguments($arguments);
    }

    public function parseResponse($data)
    {
        if (count($data) > 1) {
            $result = [];

            for ($i = 0, $iMax = count($data); $i < $iMax; ++$i) {
                if (array_key_exists($i + 1, $data)) {
                    $result[(string) $data[$i]] = $data[++$i];
                }
            }

            return $result;
        }

        return $data;
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
