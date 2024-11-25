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

namespace Predis\Command\Argument\Search\SchemaFields;

class TextField extends AbstractField
{
    /**
     * @param string      $identifier
     * @param string      $alias
     * @param bool|string $sortable
     * @param bool        $noIndex
     * @param bool        $noStem
     * @param string      $phonetic
     * @param int         $weight
     * @param bool        $withSuffixTrie
     * @param bool        $allowsEmpty
     * @param bool        $allowsMissing
     */
    public function __construct(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false,
        bool $noStem = false,
        string $phonetic = '',
        int $weight = 1,
        bool $withSuffixTrie = false,
        bool $allowsEmpty = false,
        bool $allowsMissing = false
    ) {
        $this->setCommonOptions('TEXT', $identifier, $alias, $sortable, $noIndex, $allowsMissing);

        if ($noStem) {
            $this->fieldArguments[] = 'NOSTEM';
        }

        if ($phonetic !== '') {
            $this->fieldArguments[] = 'PHONETIC';
            $this->fieldArguments[] = $phonetic;
        }

        if ($weight !== 1) {
            $this->fieldArguments[] = 'WEIGHT';
            $this->fieldArguments[] = $weight;
        }

        if ($withSuffixTrie) {
            $this->fieldArguments[] = 'WITHSUFFIXTRIE';
        }

        if ($allowsEmpty) {
            $this->fieldArguments[] = 'INDEXEMPTY';
        }
    }
}
