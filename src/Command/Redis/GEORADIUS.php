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

namespace Predis\Command\Redis;

use Predis\Command\Command as RedisCommand;

/**
 * @deprecated As of Redis version 6.2.0, this command is regarded as deprecated.
 *
 * It can be replaced by GEOSEARCH and GEOSEARCHSTORE with the BYRADIUS argument
 * when migrating or writing new code.
 *
 * @see http://redis.io/commands/georadius
 */
class GEORADIUS extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GEORADIUS';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if ($arguments && is_array(end($arguments))) {
            $options = array_change_key_case(array_pop($arguments), CASE_UPPER);

            if (isset($options['WITHCOORD']) && $options['WITHCOORD'] == true) {
                $arguments[] = 'WITHCOORD';
            }

            if (isset($options['WITHDIST']) && $options['WITHDIST'] == true) {
                $arguments[] = 'WITHDIST';
            }

            if (isset($options['WITHHASH']) && $options['WITHHASH'] == true) {
                $arguments[] = 'WITHHASH';
            }

            if (isset($options['COUNT'])) {
                $arguments[] = 'COUNT';
                $arguments[] = $options['COUNT'];
            }

            if (isset($options['SORT'])) {
                $arguments[] = strtoupper($options['SORT']);
            }

            if (isset($options['STORE'])) {
                $arguments[] = 'STORE';
                $arguments[] = $options['STORE'];
            }

            if (isset($options['STOREDIST'])) {
                $arguments[] = 'STOREDIST';
                $arguments[] = $options['STOREDIST'];
            }
        }

        parent::setArguments($arguments);
    }
}
