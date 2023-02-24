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

namespace Predis\Command\Argument\Search;

use Predis\Command\Argument\ArrayableArgument;

class Schema implements ArrayableArgument
{
    public const SORTABLE = true;
    public const NOT_SORTABLE = false;
    public const SORTABLE_UNF = 'UNF';

    /**
     * @var array
     */
    private $arguments = ['SCHEMA'];

    /**
     * Adds text field to schema configuration.
     *
     * @param  string      $identifier
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @param  bool        $noStem
     * @param  string      $phonetic
     * @param  int         $weight
     * @param  bool        $withSuffixTrie
     * @return $this
     */
    public function addTextField(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false,
        bool $noStem = false,
        string $phonetic = '',
        int $weight = 1,
        bool $withSuffixTrie = false
    ): self {
        $this->setCommonOptions('TEXT', $identifier, $alias, $sortable, $noIndex);

        if ($noStem) {
            $this->arguments[] = 'NOSTEM';
        }

        if ($phonetic !== '') {
            $this->arguments[] = 'PHONETIC';
            $this->arguments[] = $phonetic;
        }

        if ($weight !== 1) {
            $this->arguments[] = 'WEIGHT';
            $this->arguments[] = $weight;
        }

        if ($withSuffixTrie) {
            $this->arguments[] = 'WITHSUFFIXTRIE';
        }

        return $this;
    }

    /**
     * Adds tag field to schema configuration.
     *
     * @param  string      $identifier
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @param  string      $separator
     * @param  bool        $caseSensitive
     * @return $this
     */
    public function addTagField(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false,
        string $separator = ',',
        bool $caseSensitive = false
    ): self {
        $this->setCommonOptions('TAG', $identifier, $alias, $sortable, $noIndex);

        if ($separator !== ',') {
            $this->arguments[] = 'SEPARATOR';
            $this->arguments[] = $separator;
        }

        if ($caseSensitive) {
            $this->arguments[] = 'CASESENSITIVE';
        }

        return $this;
    }

    /**
     * Adds numeric field to schema configuration.
     *
     * @param  string      $identifier
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    public function addNumericField(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false
    ): self {
        $this->setCommonOptions('NUMERIC', $identifier, $alias, $sortable, $noIndex);

        return $this;
    }

    /**
     * Adds geo field to schema configuration.
     *
     * @param  string      $identifier
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    public function addGeoField(
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false
    ): self {
        $this->setCommonOptions('GEO', $identifier, $alias, $sortable, $noIndex);

        return $this;
    }

    /**
     * Adds vector field to schema configuration.
     *
     * @param  string $fieldName
     * @param  string $algorithm
     * @param  array  $attributeNameValueDictionary ['attribute_name', 'attribute_value'...]
     * @return $this
     */
    public function addVectorField(
        string $fieldName,
        string $algorithm,
        array $attributeNameValueDictionary
    ): self {
        array_push($this->arguments, $fieldName, 'VECTOR', $algorithm, count($attributeNameValueDictionary));
        $this->arguments = array_merge($this->arguments, $attributeNameValueDictionary);

        return $this;
    }

    /**
     * @param  string      $fieldType
     * @param  string      $identifier
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return void
     */
    private function setCommonOptions(
        string $fieldType,
        string $identifier,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false
    ): void {
        $this->arguments[] = $identifier;

        if ($alias !== '') {
            $this->arguments[] = 'AS';
            $this->arguments[] = $alias;
        }

        $this->arguments[] = $fieldType;

        if ($sortable === self::SORTABLE) {
            $this->arguments[] = 'SORTABLE';
        } elseif ($sortable === self::SORTABLE_UNF) {
            $this->arguments[] = 'SORTABLE';
            $this->arguments[] = 'UNF';
        }

        if ($noIndex) {
            $this->arguments[] = 'NOINDEX';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
