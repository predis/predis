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

class Autoloader
{
    private $_baseDir;
    private $_prefix;

    public function __construct($baseDirectory = null)
    {
        $this->_baseDir = $baseDirectory ?: dirname(__FILE__);
        $this->_prefix = __NAMESPACE__ . '\\';
    }

    public static function register()
    {
        spl_autoload_register(array(new self, 'autoload'));
    }

    public function autoload($className)
    {
        if (0 !== strpos($className, $this->_prefix)) {
            return;
        }

        $relativeClassName = substr($className, strlen($this->_prefix));
        $classNameParts = explode('\\', $relativeClassName);

        $path = $this->_baseDir .
            DIRECTORY_SEPARATOR .
            implode(DIRECTORY_SEPARATOR, $classNameParts) .
            '.php';

        require_once $path;
    }
}
