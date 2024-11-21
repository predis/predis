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

namespace Predis\Command\Argument\Search;

class SugGetArguments extends CommonArguments
{
    /**
     * Performs a fuzzy prefix search, including prefixes at Levenshtein distance of 1 from the prefix sent.
     *
     * @return $this
     */
    public function fuzzy(): self
    {
        $this->arguments[] = 'FUZZY';

        return $this;
    }

    /**
     * Limits the results to a maximum of num (default: 5).
     *
     * @param  int   $num
     * @return $this
     */
    public function max(int $num): self
    {
        array_push($this->arguments, 'MAX', $num);

        return $this;
    }
}
