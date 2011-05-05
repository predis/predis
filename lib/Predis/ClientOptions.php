<?php

namespace Predis;

use Predis\Options\IOption;
use Predis\Options\ClientPrefix;
use Predis\Options\ClientProfile;
use Predis\Options\ClientCluster;
use Predis\Options\ClientConnectionFactory;

class ClientOptions {
    private $_handlers, $_options;
    private static $_sharedOptions;

    public function __construct(Array $options = array()) {
        $this->initialize($options);
    }

    private static function getSharedOptions() {
        if (isset(self::$_sharedOptions)) {
            return self::$_sharedOptions;
        }
        self::$_sharedOptions = array(
            'profile' => new ClientProfile(),
            'connections' => new ClientConnectionFactory(),
            'cluster' => new ClientCluster(),
            'prefix' => new ClientPrefix(),
        );
        return self::$_sharedOptions;
    }

    public static function define($option, IOption $handler) {
        self::getSharedOptions();
        self::$_sharedOptions[$option] = $handler;
    }

    public static function undefine($option) {
        self::getSharedOptions();
        unset(self::$_sharedOptions[$option]);
    }

    private function initialize($options) {
        $this->_handlers = self::getSharedOptions();
        foreach ($options as $option => $value) {
            if (isset($this->_handlers[$option])) {
                $handler = $this->_handlers[$option];
                $this->_options[$option] = $handler($value);
            }
        }
    }

    public function __get($option) {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        if (isset($this->_handlers[$option])) {
            $opts = self::getSharedOptions();
            $value = $opts[$option]->getDefault();
            $this->_options[$option] = $value;
            return $value;
        }
        return null;
    }
}
