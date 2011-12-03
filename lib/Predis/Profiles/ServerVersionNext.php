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

/**
 * Server profile for the current development version of Redis.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerVersionNext extends ServerVersion24
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.6';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array_merge(parent::getSupportedCommands(), array(
            /* commands operating on the key space */
            'pttl'                      => 'Predis\Commands\KeyPreciseTimeToLive',
            'pexpire'                   => 'Predis\Commands\KeyPreciseExpire',
            'pexpireat'                 => 'Predis\Commands\KeyPreciseExpireAt',

            /* commands operating on string values */
            'psetex'                    => 'Predis\Commands\StringPreciseSetExpire',
            'incrbyfloat'               => 'Predis\Commands\StringIncrementByFloat',

            /* commands operating on hashes */
            'hincrbyfloat'              => 'Predis\Commands\HashIncrementByFloat',

            /* scripting */
            'eval'                      => 'Predis\Commands\ServerEval',
            'evalsha'                   => 'Predis\Commands\ServerEvalSHA',
            'script'                    => 'Predis\Commands\ServerScript',

            /* remote server control commands */
            'info'                      => 'Predis\Commands\ServerInfoV26x',
        ));
    }
}
