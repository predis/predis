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

namespace Predis\Command\Argument\Search\SchemaFields;

class GeoShapeField extends AbstractField
{
    public const COORD_FLAT = 'FLAT';

    /**
     * @param string      $identifier
     * @param string      $alias
     * @param bool|string $sortable
     * @param bool        $noIndex
     * @param string|null $coordSystem Constants that represents available systems available on a class level.
     */
    public function __construct(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false,
        ?string $coordSystem = null
    ) {
        $this->fieldArguments[] = $identifier;

        if ($alias !== '') {
            $this->fieldArguments[] = 'AS';
            $this->fieldArguments[] = $alias;
        }

        $this->fieldArguments[] = 'GEOSHAPE';

        if (null !== $coordSystem) {
            $this->fieldArguments[] = $coordSystem;
        }

        if ($sortable === self::SORTABLE) {
            $this->fieldArguments[] = 'SORTABLE';
        } elseif ($sortable === self::SORTABLE_UNF) {
            $this->fieldArguments[] = 'SORTABLE';
            $this->fieldArguments[] = 'UNF';
        }

        if ($noIndex) {
            $this->fieldArguments[] = 'NOINDEX';
        }
    }
}
