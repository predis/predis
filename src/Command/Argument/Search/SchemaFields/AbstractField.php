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

abstract class AbstractField implements FieldInterface
{
    public const SORTABLE = true;
    public const NOT_SORTABLE = false;
    public const SORTABLE_UNF = 'UNF';

    /**
     * @var array
     */
    protected $fieldArguments = [];

    /**
     * @param  string      $fieldType
     * @param  string      $identifier
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @param  bool        $allowsMissing
     * @return void
     */
    protected function setCommonOptions(
        string $fieldType,
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false,
        bool $allowsMissing = false
    ): void {
        $this->fieldArguments[] = $identifier;

        if ($alias !== '') {
            $this->fieldArguments[] = 'AS';
            $this->fieldArguments[] = $alias;
        }

        $this->fieldArguments[] = $fieldType;

        if ($sortable === self::SORTABLE) {
            $this->fieldArguments[] = 'SORTABLE';
        } elseif ($sortable === self::SORTABLE_UNF) {
            $this->fieldArguments[] = 'SORTABLE';
            $this->fieldArguments[] = 'UNF';
        }

        if ($noIndex) {
            $this->fieldArguments[] = 'NOINDEX';
        }

        if ($allowsMissing) {
            $this->fieldArguments[] = 'INDEXMISSING';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->fieldArguments;
    }
}
