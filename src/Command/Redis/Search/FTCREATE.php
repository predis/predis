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

        parent::setArguments(array_merge(
            [$index],
            $optionalArguments,
            $schema->toArray()
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
            'payloadField',
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
