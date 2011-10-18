<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Protocol\Text;

use Predis\ResponseError;
use Predis\Protocol\IResponseHandler;
use Predis\Network\IConnectionComposable;

class ResponseErrorSilentHandler implements IResponseHandler
{
    public function handle(IConnectionComposable $connection, $errorMessage)
    {
        return new ResponseError($errorMessage);
    }
}
