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

use Predis\Command\Command as RedisCommand;
use Predis\Command\Traits\Aggregate;
use Predis\Command\Traits\Keys;
use Predis\Command\Traits\Weights;

/**
 * @link http://redis.io/commands/zunionstore
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ZUNIONSTORE extends RedisCommand
{
    use Keys {
        Keys::setArguments as setKeys;
    }
    use Weights {
        Weights::setArguments as setWeights;
    }
    use Aggregate{
        Aggregate::setArguments as setAggregate;
    }

    protected static $keysArgumentPositionOffset = 1;
    protected static $weightsArgumentPositionOffset = 2;
    protected static $aggregateArgumentPositionOffset = 3;

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'ZUNIONSTORE';
    }

    /**
     * {@inheritdoc}
     */
    public function setArguments(array $arguments)
    {
        // support old `$options` array for backwards compatibility
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
