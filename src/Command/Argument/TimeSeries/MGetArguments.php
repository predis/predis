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

namespace Predis\Command\Argument\TimeSeries;

class MGetArguments extends CommonArguments
{
    /**
     * Includes in the reply all label-value pairs representing metadata labels of the time series.
     *
     * @return $this
     */
    public function withLabels(): self
    {
        $this->arguments[] = 'WITHLABELS';

        return $this;
    }

    /**
     * Returns a subset of the label-value pairs that represent metadata labels of the time series.
     *
     * @return $this
     */
    public function selectedLabels(string ...$labels): self
    {
        array_push($this->arguments, 'SELECTED_LABELS', ...$labels);

        return $this;
    }
}
