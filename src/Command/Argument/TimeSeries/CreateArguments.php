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

class CreateArguments extends CommonArguments
{
    public const ENCODING_UNCOMPRESSED = 'UNCOMPRESSED';
    public const ENCODING_COMPRESSED = 'COMPRESSED';

    /**
     * Specifies the series samples encoding format.
     *
     * @param  string|null $encoding
     * @return $this
     */
    public function encoding(string $encoding = self::ENCODING_COMPRESSED): self
    {
        array_push($this->arguments, 'ENCODING', $encoding);

        return $this;
    }
}
