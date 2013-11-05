<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

use InvalidArgumentException;
use Predis\Profile\ServerProfile;
use Predis\Profile\ServerProfileInterface;

/**
 * Configures the server profile to be used by the client
 * to create command instances depending on the specified
 * version of the Redis server.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ProfileOption implements OptionInterface
{
    /**
     * Sets the needed commands processors that should be applied to the profile.
     *
     * @param OptionsInterface $options Client options.
     * @param ServerProfileInterface $profile Server profile.
     */
    protected function setProcessors(OptionsInterface $options, ServerProfileInterface $profile)
    {
        if (isset($options->prefix)) {
            $profile->setProcessor($options->prefix);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if (is_string($value)) {
            $value = ServerProfile::get($value);
            $this->setProcessors($options, $value);
        } else if (!$value instanceof ServerProfileInterface) {
            throw new InvalidArgumentException('Invalid value for the profile option');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(OptionsInterface $options)
    {
        $profile = ServerProfile::getDefault();
        $this->setProcessors($options, $profile);

        return $profile;
    }
}
