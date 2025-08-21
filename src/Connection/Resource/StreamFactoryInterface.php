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

namespace Predis\Connection\Resource;

use Predis\Connection\ParametersInterface;
use Psr\Http\Message\StreamInterface;

interface StreamFactoryInterface
{
    /**
     * Creates stream from given parameters.
     *
     * @param  ParametersInterface $parameters
     * @return StreamInterface
     */
    public function createStream(ParametersInterface $parameters): StreamInterface;
}
