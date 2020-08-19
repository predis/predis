<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Command\Processor\ProcessorInterface;
use Predis\Configuration\OptionInterface;
use Predis\Configuration\OptionsInterface;

/**
 * Configures a command processor that apply the specified prefix string to a
 * series of Redis commands considered prefixable.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class Prefix implements OptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_callable($value)) {
            $value = call_user_func($value, $options);
        }

        if ($value instanceof ProcessorInterface) {
            return $value;
        }

        return new KeyPrefixProcessor((string) $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        // NOOP
    }
}
