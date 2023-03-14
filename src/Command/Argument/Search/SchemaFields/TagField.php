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

namespace Predis\Command\Argument\Search\SchemaFields;

class TagField extends AbstractField
{
    /**
     * @param string      $identifier
     * @param string      $alias
     * @param bool|string $sortable
     * @param bool        $noIndex
     * @param string      $separator
     * @param bool        $caseSensitive
     */
    public function __construct(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false,
        string $separator = ',',
        bool $caseSensitive = false
    ) {
        $this->setCommonOptions('TAG', $identifier, $alias, $sortable, $noIndex);

        if ($separator !== ',') {
            $this->fieldArguments[] = 'SEPARATOR';
            $this->fieldArguments[] = $separator;
        }

        if ($caseSensitive) {
            $this->fieldArguments[] = 'CASESENSITIVE';
        }
    }
}
