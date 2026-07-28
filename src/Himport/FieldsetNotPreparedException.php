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

namespace Predis\Himport;

use Predis\Response\ServerException;

/**
 * Raised when a `HIMPORT SET` targets a fieldset that is not prepared on the
 * executing connection but is known to the client (declared through the himport
 * container or the `himport` option).
 *
 * It is a normal server error (`no such fieldset`) that the himport container
 * knows how to recover from by re-preparing the fieldset. It is surfaced as a
 * dedicated type so it can be registered with the connection's Retry policy:
 * the write is retried (after re-preparing) only when retries are configured,
 * exactly like any other retryable failure. With retries disabled it propagates
 * unchanged, like a plain ServerException.
 */
class FieldsetNotPreparedException extends ServerException
{
}
