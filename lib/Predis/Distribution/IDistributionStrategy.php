<?php

namespace Predis\Distribution;

interface IDistributionStrategy {
    public function add($node, $weight = null);
    public function remove($node);
    public function get($key);
    public function generateKey($value);
}
