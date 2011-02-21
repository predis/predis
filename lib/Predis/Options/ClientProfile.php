<?php

namespace Predis\Options;

use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;

class ClientProfile extends Option {
    public function validate($value) {
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

    public function getDefault() {
        return ServerProfile::getDefault();
    }
}
