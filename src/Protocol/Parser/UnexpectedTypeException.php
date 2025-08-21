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

namespace Predis\Protocol\Parser;

use Throwable;
use UnexpectedValueException;

class UnexpectedTypeException extends UnexpectedValueException
{
    /**
     * @var string
     */
    protected $type;

    public function __construct(string $type, $message = '', $code = 0, ?Throwable $previous = null)
    {
        $this->type = $type;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
