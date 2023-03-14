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

class VectorField implements FieldInterface
{
    /**
     * @var array
     */
    protected $fieldArguments = [];

    /**
     * @param string $fieldName
     * @param string $algorithm
     * @param array  $attributeNameValueDictionary
     */
    public function __construct(
        string $fieldName,
        string $algorithm,
        array $attributeNameValueDictionary
    ) {
        array_push($this->fieldArguments, $fieldName, 'VECTOR', $algorithm, count($attributeNameValueDictionary));
        $this->fieldArguments = array_merge($this->fieldArguments, $attributeNameValueDictionary);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->fieldArguments;
    }
}
