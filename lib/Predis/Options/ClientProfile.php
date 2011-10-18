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

use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;

/**
 * Option class that handles server profiles to be used by a client.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientProfile extends Option
{
    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        if ($value instanceof IServerProfile) {
            return $value;
        }

        if (is_string($value)) {
            return ServerProfile::get($value);
        }

        throw new \InvalidArgumentException(
            "Invalid value for the profile option"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return ServerProfile::getDefault();
    }
}
