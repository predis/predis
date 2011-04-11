<?php

namespace Predis\Distribution;

interface IDistributionStrategy extends INodeKeyGenerator {
    public function add($node, $weight = null);
    public function remove($node);
    public function get($key);
}
