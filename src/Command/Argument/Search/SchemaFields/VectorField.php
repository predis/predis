<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Argument\Search\SchemaFields;

class VectorField extends AbstractField
{
    /**
     * @var array
     */
    protected $fieldArguments = [];

    /**
     * Attributes are a flat list of alternating names and values and are sent
     * to the server as-is, e.g. for an SQ8-compressed HNSW field (Redis 8.12+):
     *
     * new VectorField('v', 'HNSW', [
     *     'TYPE', 'FLOAT32', 'DIM', 64, 'DISTANCE_METRIC', 'L2',
     *     'COMPRESSION', 'SQ8', 'TRAINING_THRESHOLD', 4096,
     * ]);
     *
     * @param string $fieldName
     * @param string $algorithm
     * @param array  $attributeNameValueDictionary
     * @param string $alias
     */
    public function __construct(
        string $fieldName,
        string $algorithm,
        array $attributeNameValueDictionary,
        string $alias = ''
    ) {
        $this->setCommonOptions('VECTOR', $fieldName, $alias);

        array_push($this->fieldArguments, $algorithm, count($attributeNameValueDictionary));
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
