<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

use Predis\Distribution\INodeKeyGenerator;

interface ICommand
{
    public function getId();
    public function getHash(INodeKeyGenerator $distributor);
    public function setArguments(Array $arguments);
    public function getArguments();
    public function prefixKeys($prefix);
    public function parseResponse($data);
}
