<?php

namespace Predis\Distribution;

interface INodeKeyGenerator {
    public function generateKey($value);
}
