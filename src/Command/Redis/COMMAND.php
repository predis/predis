<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
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
    public function setArguments(array $arguments)
    {
        if (array_key_exists(0, $arguments) && $arguments[0] === 'LIST') {
            $this->setListArguments($arguments);
        } else {
            parent::setArguments($arguments);
        }
    }

    /**
     * @param  array $arguments
     * @return void
     */
    private function setListArguments(array $arguments): void
    {
        $processedArguments = [$arguments[0]];
        $filterArguments = [];

        if (array_key_exists(1, $arguments) && null !== $arguments[1]) {
            $filterArguments = $arguments[1]->toArray();
        }

        parent::setArguments(array_merge(
            $processedArguments,
            $filterArguments
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse($data)
    {
        // Relay (RESP3) uses maps and it might be good
        // to make the return value a breaking change

        return $data;
    }
}
