<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\SchemaFields\FieldInterface;
use Predis\Command\Command as RedisCommand;

class FTALTER extends RedisCommand
{
    public function getId()
    {
        return 'FT.ALTER';
    }

    public function setArguments(array $arguments)
    {
        [$index, $schema] = $arguments;
        $commandArguments = (!empty($arguments[2])) ? $arguments[2]->toArray() : [];

        $schema = array_reduce($schema, static function (array $carry, FieldInterface $field) {
            return array_merge($carry, $field->toArray());
        }, []);

        array_unshift($schema, 'SCHEMA', 'ADD');

        parent::setArguments(array_merge(
            [$index],
            $commandArguments,
            $schema
        ));
    }
}
