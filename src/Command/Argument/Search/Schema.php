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
     * @param bool $additive Defines if schema should be additive, for additional attributes
     */
    public function __construct(bool $additive = false)
    {
        if ($additive) {
            $this->arguments[] = 'ADD';
        }
    }

    /**
     * Adds text field to schema configuration.
     *
     * @param  string      $fieldName
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    public function addTextField(
        string $fieldName,
        string $alias = '',
        $sortable = false,
        bool $noIndex = false
    ): self {
        return $this->addField('TEXT', $fieldName, $alias, $sortable, $noIndex);
    }

    /**
     * Adds tag field to schema configuration.
     *
     * @param  string      $fieldName
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    public function addTagField(
        string $fieldName,
        string $alias = '',
        $sortable = false,
        bool $noIndex = false
    ): self {
        return $this->addField('TAG', $fieldName, $alias, $sortable, $noIndex);
    }

    /**
     * Adds numeric field to schema configuration.
     *
     * @param  string      $fieldName
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    public function addNumericField(
        string $fieldName,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false
    ): self {
        return $this->addField('NUMERIC', $fieldName, $alias, $sortable, $noIndex);
    }

    /**
     * Adds geo field to schema configuration.
     *
     * @param  string      $fieldName
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    public function addGeoField(
        string $fieldName,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false
    ): self {
        return $this->addField('GEO', $fieldName, $alias, $sortable, $noIndex);
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
     * Adds given field to schema configuration.
     *
     * @param  string      $type
     * @param  string      $fieldName
     * @param  string      $alias
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return $this
     */
    private function addField(
        string $type,
        string $fieldName,
        string $alias = '',
        $sortable = self::NOT_SORTABLE,
        bool $noIndex = false
    ): self {
        $this->arguments[] = $fieldName;
        $this->setAlias($alias);
        $this->arguments[] = $type;
        $this->setFieldConfiguration($sortable, $noIndex);

        return $this;
    }

    /**
     * @param  bool|string $sortable
     * @param  bool        $noIndex
     * @return void
     */
    private function setFieldConfiguration($sortable, bool $noIndex): void
    {
        if ($sortable) {
            $this->setSortable($sortable);
        }

        if ($noIndex) {
            $this->setNoIndex();
        }
    }

    /**
     * @param  string $alias
     * @return void
     */
    private function setAlias(string $alias): void
    {
        if ($alias !== '') {
            $this->arguments[] = 'AS';
            $this->arguments[] = $alias;
        }
    }

    /**
     * @param  bool|string $sortable
     * @return void
     */
    private function setSortable($sortable): void
    {
        $this->arguments[] = 'SORTABLE';

        if ($sortable === self::SORTABLE_UNF) {
            $this->arguments[] = 'UNF';
        }
    }

    /**
     * @return void
     */
    private function setNoIndex(): void
    {
        $this->arguments[] = 'NOINDEX';
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->arguments;
    }
}
