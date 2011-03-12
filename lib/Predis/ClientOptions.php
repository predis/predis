<?php

namespace Predis;

use Predis\Options\IOption;
use Predis\Options\CustomOption;
use Predis\Options\ClientProfile;
use Predis\Options\ClientKeyDistribution;

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
            'key_distribution' => new ClientKeyDistribution(),
            'on_connection_initialized' => new CustomOption(array(
                'validate' => function($value) {
                    if (is_callable($value)) {
                        return $value;
                    }
                },
            )),
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
        $this->_handlers = $this->getOptions();
        foreach ($options as $option => $value) {
            if (isset($this->_handlers[$option])) {
                $handler = $this->_handlers[$option];
                $this->_options[$option] = $handler($value);
            }
        }
    }

    private function getOptions() {
        return self::getSharedOptions();
    }

    public function __get($option) {
        if (!isset($this->_options[$option])) {
            if (!isset($this->_handlers[$option])) {
                return null;
            }
            $handler = $this->_handlers[$option];
            $this->_options[$option] = $handler->getDefault();
        }
        return $this->_options[$option];
    }

    public function __isset($option) {
        return isset(self::$_sharedOptions[$option]);
    }
}
