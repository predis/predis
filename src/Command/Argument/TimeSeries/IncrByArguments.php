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

namespace Predis\Command\Argument\TimeSeries;

class IncrByArguments extends CommonArguments
{
    /**
     * Is (integer) UNIX sample timestamp in milliseconds or * to set the timestamp according to the server clock.
     *
     * @param  string|int $timeStamp
     * @return $this
     */
    public function timestamp($timeStamp): self
    {
        array_push($this->arguments, 'TIMESTAMP', $timeStamp);

        return $this;
    }

    /**
     * Changes data storage from compressed (default) to uncompressed.
     *
     * @return $this
     */
    public function uncompressed(): self
    {
        $this->arguments[] = 'UNCOMPRESSED';

        return $this;
    }
}
