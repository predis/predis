<?php

namespace Predis;

class ClientOptions {
    private $_handlers, $_options;
    private static $_sharedOptions;

    public function __construct($options = null) {
        $this->initialize($options ?: array());
    }

    private static function getSharedOptions() {
        if (isset(self::$_sharedOptions)) {
            return self::$_sharedOptions;
        }
        self::$_sharedOptions = array(
            'profile' => new Options\ClientProfile(),
            'key_distribution' => new Options\ClientKeyDistribution(),
            'on_connection_initialized' => new Options\CustomOption(array(
                'validate' => function($value) {
                    if (isset($value) && is_callable($value)) {
                        return $value;
                    }
                },
            )),
        );
        return self::$_sharedOptions;
    }

    public static function define($option, Options\IOption $handler) {
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
