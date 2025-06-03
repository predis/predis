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

class VGETATTR extends RedisCommand
{
    /**
     * @var bool
     */
    private $asJson = false;

    /**
     * @return string
     */
    public function getId()
    {
        return 'VGETATTR';
    }

    /**
     * @param  array $arguments
     * @return void
     */
    public function setArguments(array $arguments)
    {
        $lastArg = array_pop($arguments);

        if (is_bool($lastArg)) {
            $this->asJson = $lastArg;
        } else {
            $arguments[] = $lastArg;
        }

        parent::setArguments($arguments);
    }

    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parseResponse($data)
    {
        if (!$this->asJson && !is_null($data)) {
            return json_decode($data, true);
        }

        return $data;
    }

    /**
     * @param                          $data
     * @return array|mixed|string|null
     */
    public function parseResp3Response($data)
    {
        return $this->parseResponse($data);
    }
}
