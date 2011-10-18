<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use Predis\Options\IOption;
use Predis\Options\ClientPrefix;
use Predis\Options\ClientProfile;
use Predis\Options\ClientCluster;
use Predis\Options\ClientConnectionFactory;

class ClientOptions
{
    private static $_sharedOptions;

    private $_handlers;
    private $_defined;

    private $_options = array();

    public function __construct(Array $options = array())
    {
        $this->_handlers = $this->initialize($options);
        $this->_defined = array_keys($options);
    }

    private static function getSharedOptions()
    {
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

    public static function define($option, IOption $handler)
    {
        self::getSharedOptions();
        self::$_sharedOptions[$option] = $handler;
    }

    public static function undefine($option)
    {
        self::getSharedOptions();
        unset(self::$_sharedOptions[$option]);
    }

    private function initialize($options)
    {
        $handlers = self::getSharedOptions();

        foreach ($options as $option => $value) {
            if (isset($handlers[$option])) {
                $handler = $handlers[$option];
                $handlers[$option] = function() use($handler, $value) {
                    return $handler->validate($value);
                };
            }
        }

        return $handlers;
    }

    public function __isset($option)
    {
        return in_array($option, $this->_defined);
    }

    public function __get($option)
    {
        if (isset($this->_options[$option])) {
            return $this->_options[$option];
        }

        if (isset($this->_handlers[$option])) {
            $handler = $this->_handlers[$option];
            $value = $handler instanceof IOption ? $handler->getDefault() : $handler();
            $this->_options[$option] = $value;

            return $value;
        }
    }
}
