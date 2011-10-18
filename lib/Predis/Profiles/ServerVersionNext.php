<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profiles;

class ServerVersionNext extends ServerVersion24
{
    public function getVersion()
    {
        return '2.6';
    }

    public function getSupportedCommands()
    {
        return array_merge(parent::getSupportedCommands(), array(
            'info'                      => '\Predis\Commands\ServerInfoV26x',
            'eval'                      => '\Predis\Commands\ServerEval',
            'evalsha'                   => '\Predis\Commands\ServerEvalSHA',
        ));
    }
}
