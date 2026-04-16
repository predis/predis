<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see https://redis.io/commands/gcra/
 *
 * Rate limit via GCRA. tokens_per_period are allowed per period (in seconds)
 * at a sustained rate. max_burst allows for occasional spikes by granting up to
 * max_burst additional tokens to be consumed at once.
 */
class GCRA extends RedisCommand
{
    /**
     * @var string[]
     */
    private $responseSchema = [
        'limited',
        'maxRequests',
        'availableRequests',
        'retryAfter',
        'fullBurstAfter',
    ];

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'GCRA';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $processedArguments = array_slice($arguments, 0, 4);

        // TOKENS option
        if (isset($arguments[4]) && $arguments[4] !== null) {
            array_push($processedArguments, 'TOKENS', $arguments[4]);
        }

        parent::setArguments($processedArguments);
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        return array_combine($this->responseSchema, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function parseResp3Response($data)
    {
        return $this->parseResponse($data);
    }

    /**
     * {@inheritdoc}
     */
    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
