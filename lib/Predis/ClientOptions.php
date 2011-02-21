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
            'iterable_multibulk' => new Options\ClientIterableMultiBulk(),
            'throw_errors' => new Options\ClientThrowOnError(),
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

    protected function defineOption($name, Options\IOption $option) {
        $this->_handlers[$name] = $option;
    }

    public function __get($option) {
        if (!isset($this->_options[$option])) {
            $handler = $this->_handlers[$option];
            $this->_options[$option] = $handler->getDefault();
        }
        return $this->_options[$option];
    }

    public function __isset($option) {
        return isset(self::$_sharedOptions[$option]);
    }
}
