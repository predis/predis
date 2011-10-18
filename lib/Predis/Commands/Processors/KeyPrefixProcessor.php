<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands\Processors;

use Predis\Commands\ICommand;

class KeyPrefixProcessor implements ICommandProcessor
{
    private $_prefix;

    public function __construct($prefix)
    {
        $this->setPrefix($prefix);
    }

    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->_prefix;
    }

    public function process(ICommand $command)
    {
        $command->prefixKeys($this->_prefix);
    }
}
