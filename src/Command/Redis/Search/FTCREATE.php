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

namespace Predis\Command\Redis\Search;

use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\FieldInterface;

/**
 * @see https://redis.io/commands/ft.create/
 *
 * Create an index with the given specification
 */
class FTCREATE extends WithOptionalArguments
{
    public function getId()
    {
        return 'FT.CREATE';
    }

    public function setArguments(array $arguments)
    {
        [$index, $schema] = array_splice($arguments, 0, 2);
        $optionalArguments = $this->buildOptionalArguments(new CreateArguments(), $arguments);

        $schema = array_reduce($schema, static function (array $carry, FieldInterface $field) {
            return array_merge($carry, $field->toArray());
        }, []);

        array_unshift($schema, 'SCHEMA');

        parent::setArguments(array_merge(
            [$index],
            $optionalArguments,
            $schema
        ));
    }

    public function getOptionalArguments(): array
    {
        return [
            'on',
            'prefix',
            'filter',
            'language',
            'languageField',
            'score',
            'scoreField',
            'maxTextFields',
            'temporary',
            'noOffsets',
            'noHl',
            'noFields',
            'noFreqs',
            'stopWords',
            'skipInitialScan',
        ];
    }
}
