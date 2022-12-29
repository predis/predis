<?php

namespace Predis\Command\Redis\BloomFilters;

use Predis\Command\Command as RedisCommand;
use Predis\Response\Status;
use UnexpectedValueException;

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
        'expansion' => 'EXPANSION'
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
                if ($data[$i + 1] ?? false) {
                    $result[(string)$data[$i]] = $data[++$i];
                }
            }

            return $result;
        }

        return $data;
    }
}
