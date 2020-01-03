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

/**
 * Class ZSetRemoveMaxByScore
 * @package Predis\Command
 *
 * @link https://redis.io/commands/zpopmax
 *
 * @author Ahmed Raafat <ahmed.raafat1412@gmail.com>
 */
class ZSetRemoveMaxByScore extends Command
{
    /**
     * @inheritDoc
     */
    public function getId()
    {
        return 'ZPOPMAX';
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        $response = array();

        $responseCount = count($data);
        for ($i = 0; $i < $responseCount; $i++) {
            $response[$data[$i]] = $data[++$i];
        }

        return $response;
    }
}
