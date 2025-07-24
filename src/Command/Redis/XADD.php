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

use Predis\Command\PrefixableCommand as RedisCommand;

/**
 * @see http://redis.io/commands/xadd
 */
class XADD extends RedisCommand
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'XADD';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        $args = [];

        $args[] = $arguments[0];

        $optionsOffset = 3;
        $idOffset = 2;
        $pushRefArg = false;

        if (is_array($arguments) && count(array_intersect(['KEEPREF', 'DELREF', 'ACKED'], $arguments)) == 1) {
            ++$optionsOffset;
            ++$idOffset;
            $pushRefArg = true;
        }

        $options = $arguments[$optionsOffset] ?? [];

        if (isset($options['nomkstream']) && $options['nomkstream']) {
            $args[] = 'NOMKSTREAM';
        }

        if (isset($options['trim']) && is_array($options['trim'])) {
            array_push($args, ...$options['trim']);

            if (isset($options['limit'])) {
                $args[] = 'LIMIT';
                $args[] = $options['limit'];
            }
        }

        if ($pushRefArg) {
            $args[] = array_intersect(['KEEPREF', 'DELREF', 'ACKED'], $arguments)[0];
        }

        // ID, default to * to let Redis set it
        $args[] = $arguments[$idOffset] ?? '*';
        if (isset($arguments[$idOffset - 1]) && is_array($arguments[$idOffset - 1])) {
            foreach ($arguments[$idOffset - 1] as $key => $val) {
                $args[] = $key;
                $args[] = $val;
            }
        }

        parent::setArguments($args);
    }

    public function prefixKeys($prefix)
    {
        $this->applyPrefixForFirstArgument($prefix);
    }
}
