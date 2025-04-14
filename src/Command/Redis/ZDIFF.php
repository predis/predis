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

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\With\WithScores;

/**
 * @see https://redis.io/commands/zdiff/
 *
 * This command is similar to ZDIFFSTORE, but instead of
 * storing the resulting sorted set, it is returned to the client.
 */
class ZDIFF extends RedisCommand
{
    use WithScores {
        WithScores::setArguments as setWithScore;
    }
    use Keys {
        Keys::setArguments as setKeys;
    }

    protected static $keysArgumentPositionOffset = 0;

    public function getId()
    {
        return 'ZDIFF';
    }

    public function setArguments(array $arguments)
    {
        $this->setKeys($arguments);
        $arguments = $this->getArguments();

        $this->setWithScore($arguments);
    }

    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parseResp3Response($data)
    {
        $parsedData = [];

        foreach ($data as $element) {
            $parsedData[] = $this->parseResponse($element);
        }

        return $parsedData;
    }
}
