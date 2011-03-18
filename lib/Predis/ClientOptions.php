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
            'connections' => new CustomOption(array(
                'default'  => function() {
                    return new ConnectionSchemes();
                },
                'validate' => function($value) {
                    if ($value instanceof IConnectionSchemes) {
                        return $value;
                    }
                    if (is_array($value)) {
                        return new ConnectionSchemes($value);
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
        $this->_handlers = self::getSharedOptions();
        foreach ($options as $option => $value) {
            if (isset($this->_handlers[$option])) {
                $handler = $this->_handlers[$option];
                $this->_options[$option] = $handler($value);
            }
        }
    }

    private function tryInitializeValue($option) {
        if (isset($this->_handlers[$option])) {
            $opts = self::getSharedOptions();
            $value = $opts[$option]->getDefault();
            $this->_options[$option] = $value;
            return $value;
        }
    }

    public function __get($option) {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }
        return $this->tryInitializeValue($option);
    }
}
