<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Distribution;

interface IDistributionStrategy extends INodeKeyGenerator
{
    public function add($node, $weight = null);
    public function remove($node);
    public function get($key);
}
