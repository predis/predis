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

namespace Predis\Command\Argument\Search;

use InvalidArgumentException;

class SpellcheckArguments extends CommonArguments
{
    /**
     * @var string[]
     */
    private $termsEnum = [
        'include' => 'INCLUDE',
        'exclude' => 'EXCLUDE',
    ];

    /**
     * Is maximum Levenshtein distance for spelling suggestions (default: 1, max: 4).
     *
     * @return $this
     */
    public function distance(int $distance): self
    {
        $this->arguments[] = 'DISTANCE';
        $this->arguments[] = $distance;

        return $this;
    }

    /**
     * Specifies an inclusion (INCLUDE) or exclusion (EXCLUDE) of a custom dictionary named {dict}.
     *
     * @param  string $dictionary
     * @param  string $modifier
     * @param  string ...$terms
     * @return $this
     */
    public function terms(string $dictionary, string $modifier = 'INCLUDE', string ...$terms): self
    {
        if (!in_array(strtoupper($modifier), $this->termsEnum)) {
            $enumValues = implode(', ', array_values($this->termsEnum));
            throw new InvalidArgumentException("Wrong modifier value given. Currently supports: {$enumValues}");
        }

        array_push($this->arguments, 'TERMS', $this->termsEnum[strtolower($modifier)], $dictionary, ...$terms);

        return $this;
    }
}
