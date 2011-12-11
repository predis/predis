<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Options;

/**
 * Implements a client option.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Option implements IOption
{
    /**
     * {@inheritdoc}
     */
    public function filter(IClientOptions $options, $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(IClientOptions $options)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(IClientOptions $options, $value)
    {
        if (isset($value)) {
            return $this->filter($options, $value);
        }

        return $this->getDefault($options);
    }
}
