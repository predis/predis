<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

/**
 * @deprecated As of Redis version 6.2.0, this command is regarded as deprecated.
 *
 * It can be replaced by GEOSEARCH and GEOSEARCHSTORE with the FROMMEMBER arguments
 * when migrating or writing new code.
 *
 * @link http://redis.io/commands/georadiusbymember
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class GEORADIUSBYMEMBER extends GEORADIUS
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GEORADIUSBYMEMBER';
    }
}
