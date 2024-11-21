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
 * @see http://redis.io/commands/client-list
 * @see http://redis.io/commands/client-kill
 * @see http://redis.io/commands/client-getname
 * @see http://redis.io/commands/client-setname
 */
class CLIENT extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'CLIENT';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $args = array_change_key_case($this->getArguments(), CASE_UPPER);

        switch (strtoupper($args[0])) {
            case 'LIST':
                return $this->parseClientList($data);
            case 'KILL':
            case 'GETNAME':
            case 'SETNAME':
            default:
                return $data;
        } // @codeCoverageIgnore
    }

    /**
     * Parses the response to CLIENT LIST and returns a structured list.
     *
     * @param string $data Response buffer.
     *
     * @return array
     */
    protected function parseClientList($data)
    {
        $clients = [];

        foreach (explode("\n", $data, -1) as $clientData) {
            $client = [];

            foreach (explode(' ', $clientData) as $kv) {
                @[$k, $v] = explode('=', $kv);
                $client[$k] = $v;
            }

            $clients[] = $client;
        }

        return $clients;
    }
}
