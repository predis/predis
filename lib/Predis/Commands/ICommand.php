<?php

namespace Predis\Commands;

use Predis\Distribution\INodeKeyGenerator;

interface ICommand {
    public function getId();
    public function getHash(INodeKeyGenerator $distributor);
    public function setArguments(Array $arguments);
    public function getArguments();
    public function prefixKeys($prefix);
    public function parseResponse($data);
}
