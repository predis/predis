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
 * @link http://redis.io/commands/zinterstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZINTERSTORE extends ZUNIONSTORE
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZINTERSTORE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        if (! isset($arguments[3]) && (isset($arguments[2]['weights']) || isset($arguments[2]['aggregate']))) {
            $options = array_pop($arguments);
            array_push($arguments, $options['weights'] ?? []);
            array_push($arguments, $options['aggregate'] ?? 'sum');
        }

        $this->setAggregate($arguments);
        $arguments = $this->getArguments();

        $this->setWeights($arguments);
        $arguments = $this->getArguments();

        $this->setKeys($arguments);
    }
}
