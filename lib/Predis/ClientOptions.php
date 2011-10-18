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

/**
 * Class that manages validation and conversion of client options.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ClientOptions
{
    private static $_sharedOptions;

    private $_handlers;
    private $_defined;

    private $_options = array();

    /**
     * @param array $options Array of client options.
     */
    public function __construct(Array $options = array())
    {
        $this->_handlers = $this->initialize($options);
        $this->_defined = array_keys($options);
    }

    /**
     * Ensures that the default options are initialized.
     *
     * @return array
     */
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

    /**
     * Defines an option handler or overrides an existing one.
     *
     * @param string $option Name of the option.
     * @param IOption $handler Handler for the option.
     */
    public static function define($option, IOption $handler)
    {
        self::getSharedOptions();
        self::$_sharedOptions[$option] = $handler;
    }

    /**
     * Undefines the handler for the specified option.
     *
     * @param string $option Name of the option.
     */
    public static function undefine($option)
    {
        self::getSharedOptions();
        unset(self::$_sharedOptions[$option]);
    }

    /**
     * Initializes client options handlers.
     *
     * @param array $options List of client options values.
     * @return array
     */
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

    /**
     * Checks if the specified option is set.
     *
     * @param string $option Name of the option.
     * @return Boolean
     */
    public function __isset($option)
    {
        return in_array($option, $this->_defined);
    }

    /**
     * Returns the value of the specified option.
     *
     * @param string $option Name of the option.
     * @return mixed
     */
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
