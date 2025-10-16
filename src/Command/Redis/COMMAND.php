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

namespace Predis\Command\Redis;

use Predis\Command\Command as BaseCommand;

/**
 * @see http://redis.io/commands/command
 */
class COMMAND extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'COMMAND';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if ($data === array_values($data)) {
            return array_map(function ($item) {
                return $this->parseResponse($item);
            }, $data);
        }

        // Relay
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $key;
            $result[] = $this->parseResponse($value);
        }

        return $result;
    }
}
