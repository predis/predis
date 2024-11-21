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

namespace Predis;

/**
 * Exception class thrown when trying to use features not supported by certain
 * classes or abstractions of Predis.
 */
class NotSupportedException extends PredisException
{
}
