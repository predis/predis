<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Response\Error as ErrorResponse;

/**
 * @link http://redis.io/commands/echo
 *
 * @author Evgeniy Bogdanov <e.bogdanov@biz-systems.ru>
 *
 * Command is used for special case to verify that connection we use is stable, and "synced".
 * In some cases persistent connections can be broken and return partial data, or response for previous request
 *
 * This command is verifying that we're synced with server. If something went wrong - exception is thrown
 */
class ConnectionInitEcho extends ConnectionEcho
{
    public function parseResponse($data)
    {
        if ($data !== $this->getArgument(0)) {
            return new ErrorResponse("Incorrect ECHO verification response");
        }

        return $data;
    }
}
