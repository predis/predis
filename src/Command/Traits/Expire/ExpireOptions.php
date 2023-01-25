<?php

namespace Predis\Command\Traits\Expire;

trait ExpireOptions
{
    private static $argumentEnum = [
        'nx' => 'NX',
        'xx' => 'XX',
        'gt' => 'GT',
        'lt' => 'LT',
    ];

    public function setArguments(array $arguments)
    {
        $value = array_pop($arguments);

        if (in_array(strtoupper($value), self::$argumentEnum, true)) {
            $arguments[] = self::$argumentEnum[strtolower($value)];
        } else {
            $arguments[] = $value;
        }

        parent::setArguments($arguments);
    }
}
